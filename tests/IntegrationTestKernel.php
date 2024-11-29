<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Tests;

use Closure;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\FooBundle;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

use function rand;
use function sys_get_temp_dir;

class IntegrationTestKernel extends Kernel
{
    private Closure|null $servicesCallback = null;
    private int $randomKey;

    public function __construct(string $environment, bool $debug)
    {
        $this->randomKey = rand(10000000000, 99999999999);

        parent::__construct($environment, $debug);
    }

    protected function getContainerClass(): string
    {
        return 'test' . $this->randomKey . parent::getContainerClass();
    }

    /**
     * {@inheritDoc}
     *
     * @return array<mixed, BundleInterface>
     */
    public function registerBundles(): array
    {
        return [
            new DoctrineFixturesBundle(),
            new FooBundle(),
        ];
    }

    public function addServices(Closure $callback): void
    {
        $this->servicesCallback = $callback;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $c): void {
            if (! $c->hasDefinition('kernel')) {
                $c->register('kernel', static::class)
                  ->setSynthetic(true)
                  ->setPublic(true);
            }

            $c->register('doctrine', ManagerRegistry::class);

            $callback = $this->servicesCallback;
            $callback($c);

            $c->addObjectResource($this);
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/doctrine_fixtures_bundle' . $this->randomKey;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir();
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $container->findDefinition('doctrine')->setPublic(true);
            }
        });
    }
}
