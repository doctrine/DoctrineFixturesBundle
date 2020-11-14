<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Tests\Command;

use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use TypeError;
use const PHP_VERSION_ID;
use function sprintf;

class LoadDataFixturesDoctrineCommandTest extends TestCase
{
    /**
     * @group legacy
     * @expectedDeprecation Argument 2 of Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand::__construct() expects an instance of Doctrine\Persistence\ManagerRegistry, not passing it will throw a \TypeError in DoctrineFixturesBundle 4.0.
     */
    public function testInstantiatingWithoutManagerRegistry() : void
    {
        $loader = new SymfonyFixturesLoader(new Container());

        try {
            new LoadDataFixturesDoctrineCommand($loader);
        } catch (TypeError $e) {
            if (PHP_VERSION_ID >= 80000) {
                $this->expectExceptionMessage(
                    <<<'MESSAGE'
Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand::__construct(): Argument #1 ($doctrine) must be of type Doctrine\Persistence\ManagerRegistry, null given, called in /home/runner/work/DoctrineFixturesBundle/DoctrineFixturesBundle/Command/LoadDataFixturesDoctrineCommand.php on line 49
MESSAGE
                );
                throw $e;
            }
            $this->expectExceptionMessage(sprintf(
                'Argument 1 passed to Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand::__construct() must be an instance of %s, null given',
                ManagerRegistry::class
            ));

            throw $e;
        }
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testInstantiatingWithManagerRegistry() : void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $loader   = new SymfonyFixturesLoader(new Container());

        new LoadDataFixturesDoctrineCommand($loader, $registry);
    }
}
