<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Tests\Loader;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures\OtherFixtures;
use Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures\WithDependenciesFixtures;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use function class_alias;
use function interface_exists;

/**
 * @covers \Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader::getFixturesDependencyTree
 * @covers \Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader::resolveFixturesDependencyTree
 */
class ResolveFixturesSubtreeTest extends TestCase
{
    /** @var SymfonyFixturesLoader */
    private $loader;

    public static function setUpBeforeClass() : void
    {
        if (interface_exists(ObjectManager::class)) {
            return;
        }

        class_alias('Doctrine\Common\Persistence\ObjectManager', 'Doctrine\Persistence\ObjectManager', false);
    }

    protected function setUp() : void
    {
        $this->loader = new SymfonyFixturesLoader(new Container());
    }

    public function testGetBasicFixturesTree() : void
    {
        $fixtures = new OtherFixtures();
        $this->loader->addFixture($fixtures);

        $tree = $this->loader->getFixturesDependencyTree([$fixtures]);

        static::assertCount(1, $tree);
        static::assertContains($fixtures, $tree);
    }

    public function testGetFixturesTreeByClassname() : void
    {
        $fixtures = new OtherFixtures();
        $this->loader->addFixture($fixtures);

        $tree = $this->loader->getFixturesDependencyTree([OtherFixtures::class]);

        static::assertCount(1, $tree);
        static::assertContains($fixtures, $tree);
    }

    public function testResolveDependentFixtures() : void
    {
        $otherFixtures = new OtherFixtures();
        $this->loader->addFixture($otherFixtures);

        $withDependenciesFixtures = new WithDependenciesFixtures();
        $this->loader->addFixture($withDependenciesFixtures);

        $tree = $this->loader->getFixturesDependencyTree([$withDependenciesFixtures]);

        static::assertCount(2, $tree);
        static::assertSame([$otherFixtures, $withDependenciesFixtures], $tree);
    }

    public function testOmitFixturesOutsideTree() : void
    {
        $otherFixtures = new OtherFixtures();
        $this->loader->addFixture($otherFixtures);

        $withDependenciesFixtures = new WithDependenciesFixtures();
        $this->loader->addFixture($withDependenciesFixtures);

        $omittedFixtures = $this->createFixture([]);
        $this->loader->addFixture($omittedFixtures);

        $tree = $this->loader->getFixturesDependencyTree([$withDependenciesFixtures]);

        static::assertCount(2, $tree);
        static::assertSame([$otherFixtures, $withDependenciesFixtures], $tree);
    }

    public function testResolveRecursively() : void
    {
        $otherFixtures = new OtherFixtures();
        $this->loader->addFixture($otherFixtures);

        $withDependenciesFixtures = new WithDependenciesFixtures();
        $this->loader->addFixture($withDependenciesFixtures);

        $treeTopFixtures = $this->createFixture([WithDependenciesFixtures::class]);
        $this->loader->addFixture($treeTopFixtures);

        $tree = $this->loader->getFixturesDependencyTree([$treeTopFixtures]);

        static::assertCount(3, $tree);
        static::assertSame([$otherFixtures, $withDependenciesFixtures, $treeTopFixtures], $tree);
    }

    private function createFixture(array $dependencies) : FixtureInterface
    {
        return new class ($dependencies) implements FixtureInterface, DependentFixtureInterface {
            /** @var string[] */
            private $dependencies;

            public function __construct(array $dependencies)
            {
                $this->dependencies = $dependencies;
            }

            public function load(ObjectManager $manager) : void
            {
            }

            public function getDependencies() : array
            {
                return $this->dependencies;
            }
        };
    }
}
