### Bundles

### FOSUserBundle 
The Symfony Security component provides a flexible security framework that allows you to load users from configuration, a database, or anywhere else you can imagine. The FOSUserBundle builds on top of this to make it quick and easy to store users in a database.

[FOSUserBundle](https://symfony.com/doc/master/bundles/FOSUserBundle/index.html)


```bash
composer require friendsofsymfony/user-bundle
```

Enable the bundle in the kernel:
```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new FOS\UserBundle\FOSUserBundle(),
        // ...
    );
}
```


and more

        "jms/serializer-bundle": "^1.1",
        "league/fractal": "^0.13.0",
        "league/pipeline": "^0.3.0",
        "lexik/jwt-authentication-bundle": "^2.2",
        "nelmio/api-doc-bundle": "^2.13",
        "nelmio/cors-bundle": "^1.5",
        "pagerfanta/pagerfanta": "^1.0",
        "sensio/distribution-bundle": "^5.0",
        "sensio/framework-extra-bundle": "^3.0.2",
        "symfony/monolog-bundle": "^3.0.2",
        "symfony/polyfill-apcu": "^1.0",
        "symfony/psr-http-message-bridge": "^1.0",
        "symfony/swiftmailer-bundle": "^2.3.10",
        "symfony/symfony": "3.2.*",
        "twig/twig": "^1.0||^2.0",
        "zendframework/zend-code": "3.0.2",
        "zendframework/zend-diactoros": "^1.3",
        "zendframework/zend-hydrator": "^2.2",
        "zendframework/zend-stdlib": "^3.0"


dev

        "sensio/generator-bundle": "^3.0",
        "symfony/phpunit-bridge": "^3.0",
        "liip/functional-test-bundle": "^1.7",
        "hautelook/alice-bundle": "^1.4",
        "phpunit/phpunit": "^5.7",
        "doctrine/data-fixtures": "^1.2",
        "mockery/mockery": "^0.9.7",
        "brianium/paratest": "^0.13",
        "squizlabs/php_codesniffer": "^2.7",
        "friendsofphp/php-cs-fixer": "^2.0",
        "fzaninotto/faker": "^1.6",
        "sebastian/phpcpd": "^2.0",
        "pdepend/pdepend": "^2.4",
        "phpmd/phpmd": "^2.5",
        "doctrine/doctrine-fixtures-bundle": "^2.3",
        "phploc/phploc": "^3.0",
        "theseer/phpdox": "^0.9.0",
        "phpstan/phpstan": "^0.6.4"
