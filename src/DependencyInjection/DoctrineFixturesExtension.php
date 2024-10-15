<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\DependencyInjection;

use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass;
use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Exception;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

use function dirname;

class DoctrineFixturesExtension extends Extension
{
    /**
     * {@inheritDoc}
     *
     * @return void
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(dirname(__DIR__) . '/../config'));

        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config        = $this->processConfiguration($configuration, $configs);

        $container->setParameter('doctrine_fixtures.use_container_aware_loader', $config['use_container_aware_loader']);

        $container->registerForAutoconfiguration(ORMFixtureInterface::class)
            ->addTag(FixturesCompilerPass::FIXTURE_TAG);
    }

    /**
     * {@inheritDoc}
     *
     * @param array $config
     *
     * @return ConfigurationInterface|null
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new Configuration();
    }
}
