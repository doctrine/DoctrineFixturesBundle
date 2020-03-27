<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Tests;

use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\FooBundle;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use function rand;
use function sys_get_temp_dir;

class IntegrationTestKernel extends Kernel
{
    /** @var callable */
    private $servicesCallback;

    /** @var int */
    private $randomKey;

    public function __construct(string $environment, bool $debug)
    {
        $this->randomKey = rand(100, 999);

        parent::__construct($environment, $debug);
    }

    protected function getContainerClass() : string
    {
        return 'test' . $this->randomKey . parent::getContainerClass();
    }

    public function registerBundles() : array
    {
        return [
            new DoctrineFixturesBundle(),
            new FooBundle(),
        ];
    }

    public function addServices(callable $callback) : void
    {
        $this->servicesCallback = $callback;
    }

    public function registerContainerConfiguration(LoaderInterface $loader) : void
    {
        $loader->load(function (ContainerBuilder $c) : void {
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

    public function getCacheDir() : string
    {
        return sys_get_temp_dir() . '/doctrine_fixtures_bundle' . $this->randomKey;
    }

    public function getLogDir() : string
    {
        return sys_get_temp_dir();
    }
}
