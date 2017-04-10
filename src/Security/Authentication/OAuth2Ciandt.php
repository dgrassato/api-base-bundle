<?php

namespace BaseBundle\Security\Authentication;

use OAuth2\IOAuth2Storage;
use OAuth2\OAuth2;
use OAuth2\Model\IOAuth2Client;
use OAuth2\OAuth2ServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OAuth2Ciandt extends OAuth2
{
    /**
     * Extend super constructor.
     */
    public function __construct(IOAuth2Storage $storage, $config = array())
    {
        parent::__construct($storage, $config);
    }

    public function grantAccessToken(Request $request = null)
    {
        $filters = array(
            'grant_type' => array(
                'filter' => FILTER_VALIDATE_REGEXP,
                'options' => array('regexp' => self::GRANT_TYPE_REGEXP),
                'flags' => FILTER_REQUIRE_SCALAR,
            ),
            'scope' => array('flags' => FILTER_REQUIRE_SCALAR),
            'code' => array('flags' => FILTER_REQUIRE_SCALAR),
            'redirect_uri' => array('filter' => FILTER_SANITIZE_URL),
            'username' => array('flags' => FILTER_REQUIRE_SCALAR),
            'password' => array('flags' => FILTER_REQUIRE_SCALAR),
            'refresh_token' => array('flags' => FILTER_REQUIRE_SCALAR),
            'include_user' => false,
        );

        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        // Input data by default can be either POST or GET
        if ($request->getMethod() === 'POST') {
            $inputData = $request->request->all();
        } else {
            $inputData = $request->query->all();
        }

        // Basic authorization header
        $authHeaders = $this->getAuthorizationHeader($request);

        // Filter input data
        $input = filter_var_array($inputData, $filters);

        // Grant Type must be specified.
        if (!$input['grant_type']) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');
        }

        // Authorize the client
        $clientCredentials = $this->getClientCredentials($inputData, $authHeaders);

        $client = $this->storage->getClient($clientCredentials[0]);

        if (!$client) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_CLIENT, 'The client credentials are invalid');
        }

        if ($user = $this->storage->checkClientCredentials($client, $clientCredentials[1]) === false) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_CLIENT, 'The client credentials are invalid');
        }

        if (!$this->storage->checkRestrictedGrantType($client, $input['grant_type'])) {
            throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_UNAUTHORIZED_CLIENT, 'The grant type is unauthorized for this client_id');
        }

        // Do the granting
        switch ($input['grant_type']) {
            case self::GRANT_TYPE_AUTH_CODE:
                // returns array('data' => data, 'scope' => scope)
                $stored = $this->grantAccessTokenAuthCode($client, $input);
                break;
            case self::GRANT_TYPE_USER_CREDENTIALS:
                // returns: true || array('scope' => scope)
                $stored = $this->grantAccessTokenUserCredentials($client, $input);
                break;
            case self::GRANT_TYPE_CLIENT_CREDENTIALS:
                // returns: true || array('scope' => scope)
                $stored = $this->grantAccessTokenClientCredentials($client, $input, $clientCredentials);
                break;
            case self::GRANT_TYPE_REFRESH_TOKEN:
                // returns array('data' => data, 'scope' => scope)

                $stored = $this->grantAccessTokenRefreshToken($client, $input);
                break;
            default:
                if (substr($input['grant_type'], 0, 4) !== 'urn:'
                    && !filter_var($input['grant_type'], FILTER_VALIDATE_URL)
                ) {
                    throw new OAuth2ServerException(
                        self::HTTP_BAD_REQUEST,
                        self::ERROR_INVALID_REQUEST,
                        'Invalid grant_type parameter or parameter missing'
                    );
                }

                // returns: true || array('scope' => scope)
                $stored = $this->grantAccessTokenExtension($client, $inputData, $authHeaders);
        }

        if (!is_array($stored)) {
            $stored = array();
        }

        // if no scope provided to check against $input['scope'] then application defaults are set
        // if no data is provided than null is set
        $stored += array('scope' => $this->getVariable(self::CONFIG_SUPPORTED_SCOPES, null), 'data' => null,
            'access_token_lifetime' => $this->getVariable(self::CONFIG_ACCESS_LIFETIME),
            'issue_refresh_token' => true, 'refresh_token_lifetime' => $this->getVariable(self::CONFIG_REFRESH_LIFETIME), );

        $scope = $stored['scope'];
        if ($input['scope']) {
            // Check scope, if provided
            if (!isset($stored['scope']) || !$this->checkScope($input['scope'], $stored['scope'])) {
                throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_SCOPE, 'An unsupported scope was requested.');
            }
            $scope = $input['scope'];
        }

        $token = $this->createAccessToken($client, $stored['data'], $scope, $stored['access_token_lifetime'], $stored['issue_refresh_token'], $stored['refresh_token_lifetime']);

        // Fix include return user.
        if ($input['include_user']) {
            if (is_subclass_of($stored['data'], 'Symfony\Component\Security\Core\User\UserInterface')) {
                $userEntity = $stored['data'];
                $user = [
                    'username' => $userEntity->getUserName(),
                    'email' => $userEntity->getEmail(),
                    'roles' => $userEntity->getRoles(),
                ];

                $token['user'] = $user;
                $token['client_ip'] = $this->get_ip_address();
            }
        }

        return new Response(json_encode($token), 200, $this->getJsonHeaders());
    }

    private function getJsonHeaders()
    {
        $headers = $this->getVariable(self::CONFIG_RESPONSE_EXTRA_HEADERS, array());
        $headers += array(
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        );

        return $headers;
    }

    public function get_ip_address()
    {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    // trim for safety measures
                    $ip = trim($ip);
                    // attempt to validate IP
                    if ($this->validate_ip($ip)) {
                        return $ip;
                    }
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
    }

    /**
     * Ensures an ip address is both a valid IP and does not fall within
     * a private network range.
     */
    public function validate_ip($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        return true;
    }
}
