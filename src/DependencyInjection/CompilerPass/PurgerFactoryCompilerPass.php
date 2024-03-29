<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass;

use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function sprintf;

final class PurgerFactoryCompilerPass implements CompilerPassInterface
{
    public const PURGER_FACTORY_TAG = 'doctrine.fixtures.purger_factory';

    public function process(ContainerBuilder $container): void
    {
        $definition     = $container->getDefinition('doctrine.fixtures_load_command');
        $taggedServices = $container->findTaggedServiceIds(self::PURGER_FACTORY_TAG);

        $purgerFactories = [];
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $tagData) {
                if (! isset($tagData['alias'])) {
                    throw new LogicException(sprintf('Proxy factory "%s" must define an alias', $serviceId));
                }

                $purgerFactories[$tagData['alias']] = new Reference($serviceId);
            }
        }

        $definition->setArgument(2, $purgerFactories);
    }
}
