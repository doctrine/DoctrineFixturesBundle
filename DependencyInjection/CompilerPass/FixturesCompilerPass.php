<?php


namespace Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
final class FixturesCompilerPass implements CompilerPassInterface
{
    const FIXTURE_TAG = 'doctrine.fixture.orm';

    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('doctrine.fixtures.loader');
        $taggedServices = $container->findTaggedServiceIds(self::FIXTURE_TAG);

        $fixtures = [];
        foreach ($taggedServices as $serviceId => $tags) {
            $fixtures[] = new Reference($serviceId);
        }

        $definition->addMethodCall('addFixtures', [$fixtures]);
    }
}
