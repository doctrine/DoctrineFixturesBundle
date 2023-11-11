<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Tests\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\Container;
use TypeError;

use function sprintf;

use const PHP_VERSION_ID;

class LoadDataFixturesDoctrineCommandTest extends TestCase
{
    use ExpectDeprecationTrait;

    /** @group legacy */
    public function testInstantiatingWithoutManagerRegistry(): void
    {
        $loader = new SymfonyFixturesLoader(new Container());

        $this->expectDeprecation('Since doctrine/fixtures-bundle 3.2: Argument 2 of Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand::__construct() expects an instance of Doctrine\Persistence\ManagerRegistry, not passing it will throw a \TypeError in DoctrineFixturesBundle 4.0.');

        try {
            new LoadDataFixturesDoctrineCommand($loader);
        } catch (TypeError $e) {
            $this->expectExceptionMessage(sprintf(
                PHP_VERSION_ID >= 80000 ?
                    '%s::__construct(): Argument #1 ($doctrine) must be of type %s, null given' :
                    'Argument 1 passed to %s::__construct() must be an instance of %s, null given',
                DoctrineCommand::class,
                ManagerRegistry::class,
            ));

            throw $e;
        }
    }

    /** @doesNotPerformAssertions */
    public function testInstantiatingWithManagerRegistry(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $loader   = new SymfonyFixturesLoader(new Container());

        new LoadDataFixturesDoctrineCommand($loader, $registry);
    }
}
