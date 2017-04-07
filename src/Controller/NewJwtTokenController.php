<?php

namespace BaseBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Class NewJwtTokenController
 * @package BaseBundle\Controller
 */
class NewJwtTokenController extends Controller
{
    /**
     * @Route("/token/login.{_format}",
     *     defaults={
     *     "_format": "json"
     *      },
     *     requirements={
     *     "_format": "json|xml"
     *     },
     *     name="api_get_jwt_token"
     *     )
     * @Method("POST")
     * @throws \BaseBundle\Exception\ApiProblemException
     *
     * @return array
     * @ApiDoc(
     *  section="Api Authentication JWT Token",
     *  description="Generate new jwt token.",
     *  parameters={
     *  },
     *  parameters={
     *      {"name"="username", "dataType"="string", "required"=true, "description"="user.username"},
     *      {"name"="password", "dataType"="string", "required"=true, "description"="user.password"},
     *  }
     * )
     */
    public function newTokenAction(Request $request)
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');

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
}
