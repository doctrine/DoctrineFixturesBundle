<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Bundle\FixturesBundle\Purger\ORMPurgerFactory;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('doctrine.fixtures_load_command', LoadDataFixturesDoctrineCommand::class)
            ->args([
                service('doctrine.fixtures.loader'),
                service('doctrine'),
            ])
            ->tag('console.command', ['command' => 'doctrine:fixtures:load'])

        ->set('doctrine.fixtures.loader', SymfonyFixturesLoader::class)
            ->args([
                service('service_container'),
            ])

        ->set('doctrine.fixtures.purger.orm_purger_factory', ORMPurgerFactory::class)
            ->tag('doctrine.fixtures.purger_factory', ['alias' => 'default']);
};
