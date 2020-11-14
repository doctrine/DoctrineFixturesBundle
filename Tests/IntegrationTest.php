<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Tests;

use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass;
use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\PurgerFactoryCompilerPass;
use Doctrine\Bundle\FixturesBundle\Purger\PurgerFactory;
use Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures\DependentOnRequiredConstructorArgsFixtures;
use Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures\OtherFixtures;
use Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures\RequiredConstructorArgsFixtures;
use Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures\WithDependenciesFixtures;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use function array_map;
use function get_class;
use function method_exists;

class IntegrationTest extends TestCase
{
    public function testFixturesLoader() : void
    {
        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine.fixtures.loader', new Alias('doctrine.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine.fixtures.loader');

        $actualFixtures = $loader->getFixtures();
        $this->assertCount(2, $actualFixtures);
        $actualFixtureClasses = array_map(static function ($fixture) {
            return get_class($fixture);
        }, $actualFixtures);

        $this->assertSame([
            OtherFixtures::class,
            WithDependenciesFixtures::class,
        ], $actualFixtureClasses);
        $this->assertInstanceOf(WithDependenciesFixtures::class, $actualFixtures[1]);
    }

    public function testFixturesLoaderWhenFixtureHasDepdencenyThatIsNotYetLoaded() : void
    {
        // See https://github.com/doctrine/DoctrineFixturesBundle/issues/215

        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine.fixtures.loader', new Alias('doctrine.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine.fixtures.loader');

        $actualFixtures = $loader->getFixtures();
        $this->assertCount(2, $actualFixtures);
        $actualFixtureClasses = array_map(static function ($fixture) {
            return get_class($fixture);
        }, $actualFixtures);

        $this->assertSame([
            OtherFixtures::class,
            WithDependenciesFixtures::class,
        ], $actualFixtureClasses);
        $this->assertInstanceOf(WithDependenciesFixtures::class, $actualFixtures[1]);
    }

    public function testExceptionWithDependenciesWithRequiredArguments() : void
    {
        // see https://github.com/doctrine/data-fixtures/pull/274
        // When that is merged, this test will only run when using
        // an older version of that library.
        if (method_exists(Loader::class, 'createFixture')) {
            $this->markTestSkipped();
        }

        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            $c->autowire(DependentOnRequiredConstructorArgsFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->autowire(RequiredConstructorArgsFixtures::class)
                ->setArgument(0, 'foo')
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine.fixtures.loader', new Alias('doctrine.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The getDependencies() method returned a class (Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures\RequiredConstructorArgsFixtures) that has required constructor arguments. Upgrade to "doctrine/data-fixtures" version 1.3 or higher to support this.');

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine.fixtures.loader');

        $loader->getFixtures();
    }

    public function testExceptionIfDependentFixtureNotWired() : void
    {
        // only runs on newer versions of doctrine/data-fixtures
        if (! method_exists(Loader::class, 'createFixture')) {
            $this->markTestSkipped();
        }

        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            $c->autowire(DependentOnRequiredConstructorArgsFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine.fixtures.loader', new Alias('doctrine.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The "Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures\RequiredConstructorArgsFixtures" fixture class is trying to be loaded, but is not available. Make sure this class is defined as a service and tagged with "doctrine.fixture.orm".');

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine.fixtures.loader');

        $loader->getFixtures();
    }

    public function testFixturesLoaderWithGroupsOptionViaInterface() : void
    {
        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
              ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
              ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine.fixtures.loader', new Alias('doctrine.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine.fixtures.loader');

        $actualFixtures = $loader->getFixtures(['staging']);
        $this->assertCount(1, $actualFixtures);
        $actualFixtureClasses = array_map(static function ($fixture) {
            return get_class($fixture);
        }, $actualFixtures);

        $this->assertSame([
            OtherFixtures::class,
        ], $actualFixtureClasses);
        $this->assertInstanceOf(OtherFixtures::class, $actualFixtures[0]);
    }

    public function testFixturesLoaderWithGroupsOptionViaTag() : void
    {
        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG, ['group' => 'group1'])
                ->addTag(FixturesCompilerPass::FIXTURE_TAG, ['group' => 'group2']);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
              ->addTag(FixturesCompilerPass::FIXTURE_TAG, ['group' => 'group2']);

            $c->setAlias('test.doctrine.fixtures.loader', new Alias('doctrine.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine.fixtures.loader');

        $this->assertCount(1, $loader->getFixtures(['staging']));
        $this->assertCount(1, $loader->getFixtures(['group1']));
        $this->assertCount(2, $loader->getFixtures(['group2']));
        $this->assertCount(0, $loader->getFixtures(['group3']));
    }

    public function testLoadFixturesViaGroupWithMissingDependency() : void
    {
        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine.fixtures.loader', new Alias('doctrine.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine.fixtures.loader');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fixture "Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures\OtherFixtures" was declared as a dependency for fixture "Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures\WithDependenciesFixtures", but it was not included in any of the loaded fixture groups.');

        $loader->getFixtures(['missingDependencyGroup']);
    }

    public function testLoadFixturesViaGroupWithFulfilledDependency() : void
    {
        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine.fixtures.loader', new Alias('doctrine.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine.fixtures.loader');

        $actualFixtures = $loader->getFixtures(['fulfilledDependencyGroup']);

        $this->assertCount(2, $actualFixtures);
        $actualFixtureClasses = array_map(static function ($fixture) {
            return get_class($fixture);
        }, $actualFixtures);

        $this->assertSame([
            OtherFixtures::class,
            WithDependenciesFixtures::class,
        ], $actualFixtureClasses);
    }

    public function testLoadFixturesByShortName() : void
    {
        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->setAlias('test.doctrine.fixtures.loader', new Alias('doctrine.fixtures.loader', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var ContainerAwareLoader $loader */
        $loader = $container->get('test.doctrine.fixtures.loader');

        $actualFixtures = $loader->getFixtures(['OtherFixtures']);

        $this->assertCount(1, $actualFixtures);
        $actualFixtureClasses = array_map(static function ($fixture) {
            return get_class($fixture);
        }, $actualFixtures);

        $this->assertSame([
            OtherFixtures::class,
        ], $actualFixtureClasses);
    }

    public function testRunCommandWithDefaultPurger() : void
    {
        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->getDefinition('doctrine')
                ->setPublic(true)
                ->setSynthetic(true);

            $c->setAlias('test.doctrine.fixtures.purger.orm_purger_factory', new Alias('doctrine.fixtures.purger.orm_purger_factory', true));

            $c->setAlias('test.doctrine.fixtures_load_command', new Alias('doctrine.fixtures_load_command', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        $em       = $this->createConfiguredMock(EntityManagerInterface::class, ['getConnection' => $this->createMock(Connection::class), 'getEventManager' => $this->createMock(EventManager::class)]);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects(self::once())
            ->method('getManager')
            ->with(null)
            ->willReturn($em);
        $container->set('doctrine', $registry);

        $purgerFactory = $this->createMock(PurgerFactory::class);
        $purger        = $this->createMock(ORMPurger::class);
        $purgerFactory
            ->expects(self::once())
            ->method('createForEntityManager')
            ->with(null, $em, [])
            ->willReturn($purger);
        $container->set('test.doctrine.fixtures.purger.orm_purger_factory', $purgerFactory);

        /** @var LoadDataFixturesDoctrineCommand $command */
        $command = $container->get('test.doctrine.fixtures_load_command');
        $tester  = new CommandTester($command);
        $tester->execute([], ['interactive' => false]);
    }

    public function testRunCommandWithPurgeExclusions() : void
    {
        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->getDefinition('doctrine')
                ->setPublic(true)
                ->setSynthetic(true);

            $c->setAlias('test.doctrine.fixtures.purger.orm_purger_factory', new Alias('doctrine.fixtures.purger.orm_purger_factory', true));

            $c->setAlias('test.doctrine.fixtures_load_command', new Alias('doctrine.fixtures_load_command', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        $em       = $this->createConfiguredMock(EntityManagerInterface::class, ['getConnection' => $this->createMock(Connection::class), 'getEventManager' => $this->createMock(EventManager::class)]);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects(self::once())
            ->method('getManager')
            ->with(null)
            ->willReturn($em);
        $container->set('doctrine', $registry);

        $purgerFactory = $this->createMock(PurgerFactory::class);
        $purger        = $this->createMock(ORMPurger::class);
        $purgerFactory
            ->expects(self::once())
            ->method('createForEntityManager')
            ->with(null, $em, ['excluded_table'])
            ->willReturn($purger);
        $container->set('test.doctrine.fixtures.purger.orm_purger_factory', $purgerFactory);

        /** @var LoadDataFixturesDoctrineCommand $command */
        $command = $container->get('test.doctrine.fixtures_load_command');
        $tester  = new CommandTester($command);
        $tester->execute(['--purge-exclusions' => ['excluded_table']], ['interactive' => false]);
    }

    public function testRunCommandWithCustomPurgerAndCustomEntityManager() : void
    {
        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->getDefinition('doctrine')
                ->setPublic(true)
                ->setSynthetic(true);

            $c->setDefinition('test.doctrine.fixtures.purger.test_factory', (new Definition())
                ->setPublic(true)
                ->setSynthetic(true)
                ->addTag(PurgerFactoryCompilerPass::PURGER_FACTORY_TAG, ['alias' => 'test']));

            $c->setAlias('test.doctrine.fixtures_load_command', new Alias('doctrine.fixtures_load_command', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        $em       = $this->createConfiguredMock(EntityManagerInterface::class, ['getConnection' => $this->createMock(Connection::class), 'getEventManager' => $this->createMock(EventManager::class)]);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects(self::once())
            ->method('getManager')
            ->with('alternative')
            ->willReturn($em);
        $container->set('doctrine', $registry);

        $purgerFactory = $this->createMock(PurgerFactory::class);
        $purger        = $this->createMock(ORMPurger::class);
        $purgerFactory
            ->expects(self::once())
            ->method('createForEntityManager')
            ->with('alternative', $em, [])
            ->willReturn($purger);
        $container->set('test.doctrine.fixtures.purger.test_factory', $purgerFactory);

        /** @var LoadDataFixturesDoctrineCommand $command */
        $command = $container->get('test.doctrine.fixtures_load_command');
        $tester  = new CommandTester($command);
        $tester->execute(['--purger' => 'test', '--em' => 'alternative'], ['interactive' => false]);
    }

    public function testRunCommandWithPurgeMode() : void
    {
        $kernel = new IntegrationTestKernel('dev', true);
        $kernel->addServices(static function (ContainerBuilder $c) : void {
            // has a "staging" group via the getGroups() method
            $c->autowire(OtherFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            // no getGroups() method
            $c->autowire(WithDependenciesFixtures::class)
                ->addTag(FixturesCompilerPass::FIXTURE_TAG);

            $c->getDefinition('doctrine')
                ->setPublic(true)
                ->setSynthetic(true);

            $c->setAlias('test.doctrine.fixtures.purger.orm_purger_factory', new Alias('doctrine.fixtures.purger.orm_purger_factory', true));

            $c->setAlias('test.doctrine.fixtures_load_command', new Alias('doctrine.fixtures_load_command', true));
        });
        $kernel->boot();
        $container = $kernel->getContainer();

        $em       = $this->createConfiguredMock(EntityManagerInterface::class, ['getConnection' => $this->createMock(Connection::class), 'getEventManager' => $this->createMock(EventManager::class)]);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects(self::once())
            ->method('getManager')
            ->with(null)
            ->willReturn($em);
        $container->set('doctrine', $registry);

        $purgerFactory = $this->createMock(PurgerFactory::class);
        $purger        = $this->createMock(ORMPurger::class);
        $purgerFactory
            ->expects(self::once())
            ->method('createForEntityManager')
            ->with(null, $em, [], true)
            ->willReturn($purger);
        $container->set('test.doctrine.fixtures.purger.orm_purger_factory', $purgerFactory);

        /** @var LoadDataFixturesDoctrineCommand $command */
        $command = $container->get('test.doctrine.fixtures_load_command');
        $tester  = new CommandTester($command);
        $tester->execute(['--purge-with-truncate' => true], ['interactive' => false]);
    }
}
