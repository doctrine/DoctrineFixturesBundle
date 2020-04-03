<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Tests\Command;

use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Bundle\FixturesBundle\Tests\DeprecationUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use TypeError;
use function sprintf;

class LoadDataFixturesDoctrineCommandTest extends TestCase
{
    /**
     * @group legacy
     * @expectedDeprecation Argument 2 of Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand::__construct() expects an instance of Doctrine\Common\Persistence\ManagerRegistry or preferably Doctrine\Persistence\ManagerRegistry, not passing it will throw a \TypeError in DoctrineFixturesBundle 4.0.
     */
    public function testInstantiatingWithoutManagerRegistry() : void
    {
        $loader = new SymfonyFixturesLoader(new Container());

        try {
            new LoadDataFixturesDoctrineCommand($loader);
        } catch (TypeError $e) {
            $this->expectExceptionMessage(sprintf('Argument 1 passed to Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand::__construct() must be an instance of %s, null given', DeprecationUtil::getManagerRegistryClass()));

            throw $e;
        }
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testInstantiatingWithManagerRegistry() : void
    {
        $registry = $this->createMock(DeprecationUtil::getManagerRegistryClass());
        $loader   = new SymfonyFixturesLoader(new Container());

        new LoadDataFixturesDoctrineCommand($loader, $registry);
    }
}
