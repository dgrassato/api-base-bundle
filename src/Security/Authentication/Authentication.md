

Register OAuth2Ciandt in your service.yml to sobrescreve Oauth2 default implementation:

```yaml
    # Fix return access token with entity User
    fos_oauth_server.server:
        class: BaseBundle\Security\Authentication\OAuth2Ciandt
        arguments: ['@fos_oauth_server.storage', %fos_oauth_server.server.options%]
        
```   
     
Use AuthenticationSuccessListener o modify return token default.

```yaml
    
   api_auth_server.jwt.event.authentication_success_listener:
        class: BaseBundle\Security\Authentication\AuthenticationSuccessListener
        tags:
            - { name: kernel.event_listener, event: lexik_jwt_authentication.on_authentication_success, method: onAuthenticationSuccessResponse }
```     


Use Gedmo library to implement SoftDelete and Timestampable:

```yaml

    gedmo.listener.timestampable:
        class: Gedmo\Timestampable\TimestampableListener
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ "@annotation_reader" ] ]

    gedmo.listener.softdeleteablelistener:
        class: Gedmo\SoftDeleteable\SoftDeleteableListener
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ "@annotation_reader" ] ]
``` 
