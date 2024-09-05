<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doctrine_fixtures');

        $rootNode = $treeBuilder->getRootNode();

        if ($rootNode instanceof ArrayNodeDefinition) {
            $rootNode->children()
                ->booleanNode('use_container_aware_loader')
                ->defaultTrue()
                ->end();
        }

        return $treeBuilder;
    }
}
