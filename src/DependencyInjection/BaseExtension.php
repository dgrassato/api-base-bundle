<?php

namespace BaseBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class BaseExtension extends Extension
{
    protected $rootNode = 'api_base';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $variable = sprintf('%s.%s', $this->rootNode, 'entity_user_namespace');
        $container->setParameter($variable, $config['entity_user_namespace']);

        $this->loadAuthenticationParameters($config, $container);

        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');

        $this->validateBundles($container);
    }

    /**
     * @param $config Configuration
     * @param $container ContainerBuilder
     */
    private function loadAuthenticationParameters($config, ContainerBuilder $container)
    {
        foreach ($config['authentication'] as $key => $auth) {
            $variable = sprintf('%s.authentication.%s', $this->rootNode, $key);

            $container->setParameter($variable, $config['authentication'][$key]);
        }
    }

    private function validateBundles(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        $stringStatus = sprintf('%s.authentication.enabled', $this->rootNode);
        $isAuthentication = $container->getParameter($stringStatus);

        $stringMethod = sprintf('%s.authentication.method', $this->rootNode);
        $method = $container->getParameter($stringMethod);

        if ($isAuthentication && $method === 'jwt' && !isset($bundles['LexikJWTAuthenticationBundle'])) {
            throw new \RuntimeException("Please install and configure LexikJWTAuthenticationBundle bundle.\n use composer to install:\n composer require lexik/jwt-authentication-bundle");
        }

        if ($isAuthentication && $method === 'oauth2' && !isset($bundles['FOSOAuthServerBundle'])) {
            throw new \RuntimeException("Please install and configure FOSOAuthServerBundle bundle.\n use composer to install:\n composer require friendsofsymfony/oauth-server-bundle");
        }
    }
}
