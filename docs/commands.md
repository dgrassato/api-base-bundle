**Teste**

```php

php bin/console server:start

bin/console generate:bundle --namespace=CIANDT/CommonBundle --bundle-name=CommonBundle --format=annotation --dir=src --shared --no-interaction

composer dump-autoload --optimize --no-dev --classmap-authoritative
composer install --optimize-autoloader --no-scripts


bin/console doctrine:database:drop --force
bin/console doctrine:database:create
bin/console doctrine:schema:update --force

bin/console hautelook_alice:doctrine:fixtures:load -n

docker exec -it cli bin/console hautelook_alice:doctrine:fixtures:load -n 
```


bin/console doctrine:generate:entities AppBundle:Product


php app/console acme:oauth-server:client:create --redirect-uri="http://symfony.dev/" --grant-type="authorization_code" --grant-type="password" --grant-type="refresh-token" --grant-type="token" --grant-type="client_credentials"
