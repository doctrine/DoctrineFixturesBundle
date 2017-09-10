<?php

namespace Doctrine\Bundle\FixturesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * @author Tyler Stroud <tyler@tylerstroud.com>
 *
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('doctrine_fixtures', 'array');

        $this->addPathsConfiguration($rootNode);

        return $treeBuilder;
    }

    /**
     * @param NodeDefinition $root
     */
    protected function addPathsConfiguration(NodeDefinition $root)
    {
        $root
            ->children()
                ->arrayNode('paths')
                ->info('An array of paths to where your fixtures are located.')
                ->prototype('scalar')->end()
            ->end();
    }
}
