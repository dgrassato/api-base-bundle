<?php

namespace BaseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc }
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('api_base');

        $rootNode
            ->children()
                ->scalarNode('entity_user_namespace')
                    ->info('This value represents the entity user namespace.')
                    ->cannotBeEmpty()
                    ->defaultValue('AppBundle\Entity\User')
                ->end()
            ->end()

            ->children()
                ->arrayNode('authentication')

                    ->children()
                        ->integerNode('time_expiration')
                            ->info('This value represents the time, in minutes the authentication time expiration.')
                            ->isRequired()
                            ->defaultValue(3600)
                        ->end()
                    ->end()

                    ->children()
                        ->booleanNode('enabled')
                            ->info('Enable or disable authentication functions(true, false).')
                            ->defaultFalse()
                            ->isRequired()
                                ->validate()
                                    ->ifNotInArray([true, false])
                                    ->thenInvalid('Invalid authentication method %s')
                                ->end()
                        ->end()
                    ->end()


                    ->children()
                        ->scalarNode('method')
                            ->info('Choice the authentication method(oauth2, jwt).')
                            ->isRequired()
                                ->validate()
                                    ->ifNotInArray(['oauth2', 'jwt', 'oauth2_jwt'])
                                    ->thenInvalid('Invalid authentication method %s')
                                ->end()
                        ->end()
                    ->end()

                ->end() // jwt
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
