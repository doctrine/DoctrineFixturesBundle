<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle;

use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass;
use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\PurgerFactoryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DoctrineFixturesBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new FixturesCompilerPass());
        $container->addCompilerPass(new PurgerFactoryCompilerPass());
    }
}
