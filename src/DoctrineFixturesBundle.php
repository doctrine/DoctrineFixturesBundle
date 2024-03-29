<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle;

use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass;
use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\PurgerFactoryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function dirname;

class DoctrineFixturesBundle extends Bundle
{
    /** @return void */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FixturesCompilerPass());
        $container->addCompilerPass(new PurgerFactoryCompilerPass());
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
