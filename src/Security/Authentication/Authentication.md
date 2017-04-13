

Register OAuth2Server in your service.yml to modify Oauth2 implementation default:

```yaml
    # Fix return access token with entity User
    fos_oauth_server.server:
        class: BaseBundle\Security\Authentication\OAuth2\OAuth2Server
        arguments: ['@fos_oauth_server.storage', %fos_oauth_server.server.options%]
        
```   
     
Use AuthenticationSuccessListener o modify return more data information in token return.

```yaml
    
   api_auth_server.jwt.event.authentication_success_listener:
        class: BaseBundle\Security\Authentication\AuthenticationSuccessListener
        tags:
            - { name: kernel.event_listener, event: lexik_jwt_authentication.on_authentication_success, method: onAuthenticationSuccessResponse }
```     

