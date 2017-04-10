<?php

namespace BaseBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Class NewJwtTokenController.
 */
class NewJwtTokenController extends Controller
{
    /**
     * HTTP status codes for successful and error states as specified by draft 20.
     *
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-4.1.2
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-5.2
     */
    const HTTP_FOUND = '302 Found';
    const HTTP_BAD_REQUEST = '400 Bad Request';
    const HTTP_UNAUTHORIZED = '401 Unauthorized';
    const HTTP_FORBIDDEN = '403 Forbidden';
    const HTTP_UNAVAILABLE = '503 Service Unavailable';

    /**
     * @throws \BaseBundle\Exception\ApiProblemException
     *
     * @return array
     * @ApiDoc(
     *  section="Api Authentication JWT Token",
     *  description="Generate new jwt token.",
     *  parameters={
     *  },
     *  parameters={
     *      {"name"="username", "dataType"="string", "required"=true, "description"="Username"},
     *      {"name"="password", "dataType"="string", "required"=true, "description"="Password"},
     *  }
     * )
     */
    public function tokenAction(Request $request)
    {
        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        if ($request->getMethod() === 'POST') {
            $inputData = $request->request->all();
        } else {
            $inputData = $request->query->all();
        }

        $authHeaders = $this->getAuthorizationHeader($request);

        // Authorize the client
        $clientCredentials = $this->getClientCredentials($inputData, $authHeaders);

        $username = $clientCredentials['username'];
        $password = $clientCredentials['password'];

        $api = $this->get('api_base.authentication_manager');

        $user = $api->getUser($username);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        $isValid = $api->validatePassword($user, $password);
        if (!$isValid) {
            throw new BadCredentialsException();
        }

        $token = $api->encodeJwtUserAuthentication($user);
        if (!$token) {
            throw $this->createNotFoundException();
        }

        $authenticationSuccessHandler = $this->container->get('lexik_jwt_authentication.handler.authentication_success');

        return $authenticationSuccessHandler->handleAuthenticationSuccess($user, $token);
    }

    /**
     * Internal function used to get the client credentials from HTTP basic
     * auth or POST data.
     *
     * According to the spec (draft 20), the client_id can be provided in
     * the Basic Authorization header (recommended) or via GET/POST.
     *
     * @param array $inputData
     * @param array $authHeaders
     *
     * @throws OAuth2ServerException
     *
     * @return array A list containing the client identifier and password, for example
     * @code
     * return array(
     *   CLIENT_ID,
     *   CLIENT_SECRET
     * );
     * @endcode
     *
     * @see     http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-2.4.1
     */
    protected function getClientCredentials(array $inputData, array $authHeaders)
    {
        // Basic Authentication is used
        if (!empty($authHeaders['PHP_AUTH_USER'])) {
            return ['username' => $authHeaders['PHP_AUTH_USER'],
                    'password' => $authHeaders['PHP_AUTH_PW'],
            ];
        } elseif (empty($inputData['username'])) { // No credentials were specified

            throw new BadCredentialsException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_CLIENT, 'Client id was not found in the headers or body');
        } else {

            // This method is not recommended, but is supported by specification
            $username = isset($inputData['username']) ? $inputData['username'] : null;
            $password = isset($inputData['password']) ? $inputData['password'] : null;

            return ['username' => $username, 'password' => $password];
        }
    }

    /**
     * Pull out the Authorization HTTP header and return it.
     * According to draft 20, standard basic authorization is the only
     * header variable required (this does not apply to extended grant types).
     *
     * Implementing classes may need to override this function if need be.
     *
     * @todo    We may need to re-implement pulling out apache headers to support extended grant types
     *
     * @param Request $request
     *
     * @return array An array of the basic username and password provided
     *
     * @see     http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-2.4.1
     * @ingroup oauth2_section_2
     */
    protected function getAuthorizationHeader(Request $request)
    {
        if (!empty($request->server->get('PHP_AUTH_USER'))) {
            return [];
        }

        return [
            'PHP_AUTH_USER' => $request->server->get('PHP_AUTH_USER'),
            'PHP_AUTH_PW' => $request->server->get('PHP_AUTH_PW'),
        ];
    }
}
