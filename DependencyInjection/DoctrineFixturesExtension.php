<?php

namespace Doctrine\Bundle\FixturesBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * @author Tyler Stroud <tyler@tylerstroud.com>
 */
class DoctrineFixturesExtension extends Extension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['paths'])) {
            $container->setParameter('doctrine_fixtures.paths', $config['paths']);
        }
    }
}

