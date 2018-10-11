<?php


namespace Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
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
            $fixtures[] = [
                'fixture' => new Reference($serviceId),
                'groups' => $this->getFixtureGroups($serviceId, $container)
            ];

        }

        $definition->addMethodCall('addFixtures', [$fixtures]);
    }

    private function getFixtureGroups($service, ContainerBuilder $container) {
        $def = $container->getDefinition($service);
        $class = $def->getClass();

        if (!$r = $container->getReflectionClass($class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $class, $service));
        }

        $groups = [];
        if ($r->implementsInterface(FixtureGroupInterface::class)) {
            $groups = $class::getGroups();
        }

        return $groups;
    }
}
