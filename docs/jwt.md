Getting started
===============

Prerequisites
-------------

This bundle requires Symfony 2.8+ (and the OpenSSL library if you intend to use the default provided encoder).

**Protip:** Though the bundle doesn't enforce you to do so, it is highly recommended to use HTTPS. 

Installation
------------

Add [`lexik/jwt-authentication-bundle`](https://packagist.org/packages/lexik/jwt-authentication-bundle)
to your `composer.json` file:

    php composer.phar require "lexik/jwt-authentication-bundle"

Register the bundle in `app/AppKernel.php`:

``` php
public function registerBundles()
{
    return array(
        // ...
        new Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle(),
    );
}
```

Generate the SSH keys :

``` bash
mkdir -p var/jwt # For Symfony3+, no need of the -p option
openssl genrsa -out var/jwt/private.pem -aes256 4096
openssl rsa -pubout -in var/jwt/private.pem -out var/jwt/public.pem
```

Configuration
-------------

Configure the SSH keys path in your `app/config/reset_auth.yml` :

``` yaml
lexik_jwt_authentication:
    private_key_path: '%jwt_private_key_path%'
    public_key_path:  '%jwt_public_key_path%'
    pass_phrase:      '%jwt_key_pass_phrase%'
    token_ttl:        '%jwt_token_ttl%'
    # token extraction settings
    token_extractors:
        authorization_header:      # look for a token as Authorization Header
            enabled: true
            prefix:  Bearer
            name:    Authorization
        cookie:                    # check token in a cookie
            enabled: false
            name:    BEARER
        query_parameter:           # check token in query string parameter
            enabled: false
            name:    bearer
```

``` yaml
base:
  authentication:
    enabled: true
    method: "jwt"
    time_expiration: 3600
```

Configure your `parameters.yml.dist` :

``` yaml
jwt_private_key_path: '%kernel.root_dir%/../var/jwt/private.pem' # ssh private key path
jwt_public_key_path:  '%kernel.root_dir%/../var/jwt/public.pem'  # ssh public key path
jwt_key_pass_phrase:  ''                                         # ssh key pass phrase
jwt_token_ttl:        3600
```

Configure your `security.yml` :

``` yaml
security:
    # ...
    
    firewalls:

        login:
            pattern:  ^/api/login
            stateless: true
            anonymous: true
            form_login:
                check_path:               /api/login_check
                success_handler:          lexik_jwt_authentication.handler.authentication_success
                failure_handler:          lexik_jwt_authentication.handler.authentication_failure
                require_previous_session: false

        api:
            pattern:   ^/api
            stateless: true
            guard:
                authenticators:
                    - lexik_jwt_authentication.jwt_token_authenticator

    access_control:
        - { path: ^/api/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/api,       roles: IS_AUTHENTICATED_FULLY }
```
 

Usage
-----

### 1. Obtain the token

The first step is to authenticate the user using its credentials.
A classical form_login on an anonymously accessible firewall will do perfect.

Just set the provided `lexik_jwt_authentication.handler.authentication_success` service as success handler to
generate the token and send it as part of a json response body.

Store it (client side), the JWT is reusable until its ttl has expired (3600 seconds by default).

Note: You can test getting the token with a simple curl command like this:

```bash
curl -X POST http://localhost:8000/api/token/login -d _username=admin -d _password=foo
```

If it works, you will receive something like this:

```json
{
   "token" : "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJleHAiOjE0MzQ3Mjc1MzYsInVzZXJuYW1lIjoia29ybGVvbiIsImlhdCI6IjE0MzQ2NDExMzYifQ.nh0L_wuJy6ZKIQWh6OrW5hdLkviTs1_bau2GqYdDCB0Yqy_RplkFghsuqMpsFls8zKEErdX5TYCOR7muX0aQvQxGQ4mpBkvMDhJ4-pE4ct2obeMTr_s4X8nC00rBYPofrOONUOR4utbzvbd4d2xT_tj4TdR_0tsr91Y7VskCRFnoXAnNT-qQb7ci7HIBTbutb9zVStOFejrb4aLbr7Fl4byeIEYgp2Gd7gY"
}
```

Just modify the provided `lexik_jwt_authentication.handler.authentication_success` service as success handler to
generate the token to return the object of the authenticate user.

```yaml

    api_auth_server.jwt.event.authentication_success_listener:
            class: BaseBundle\Security\Authentication\AuthenticationSuccessListener
            tags:
                - { name: kernel.event_listener, event: lexik_jwt_authentication.on_authentication_success, method: onAuthenticationSuccessResponse }

```

