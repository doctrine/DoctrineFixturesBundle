<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Loader;

use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use LogicException;
use ReflectionClass;

use function array_keys;
use function array_values;
use function get_class;
use function sprintf;

final class SymfonyFixturesLoader extends SymfonyBridgeLoader
{
    /** @var FixtureInterface[] */
    private array $loadedFixtures = [];

    /** @var array<string, array<string, bool>> */
    private array $groupsFixtureMapping = [];

    /**
     * @internal
     *
     * @psalm-param list<array{fixture: FixtureInterface, groups: list<string>}> $fixtures
     */
    public function addFixtures(array $fixtures): void
    {
        // Because parent::addFixture may call $this->createFixture
        // we cannot call $this->addFixture in this loop
        foreach ($fixtures as $fixture) {
            $class                        = get_class($fixture['fixture']);
            $this->loadedFixtures[$class] = $fixture['fixture'];
            $this->addGroupsFixtureMapping($class, $fixture['groups']);
        }

        // Now that all fixtures are in the $this->loadedFixtures array,
        // it is safe to call $this->addFixture in this loop
        foreach ($this->loadedFixtures as $fixture) {
            $this->addFixture($fixture);
        }
    }

    public function addFixture(FixtureInterface $fixture): void
    {
        $class                        = get_class($fixture);
        $this->loadedFixtures[$class] = $fixture;

        $reflection = new ReflectionClass($fixture);
        $this->addGroupsFixtureMapping($class, [$reflection->getShortName()]);

        if ($fixture instanceof FixtureGroupInterface) {
            $this->addGroupsFixtureMapping($class, $fixture::getGroups());
        }

        parent::addFixture($fixture);
    }

    /**
     * Overridden to not allow new fixture classes to be instantiated.
     * {@inheritDoc}
     */
    protected function createFixture($class): FixtureInterface
    {
        /*
         * We don't actually need to create the fixture. We just
         * return the one that already exists.
         */

        if (! isset($this->loadedFixtures[$class])) {
            throw new LogicException(sprintf(
                'The "%s" fixture class is trying to be loaded, but is not available. Make sure this class is defined as a service and tagged with "%s".',
                $class,
                FixturesCompilerPass::FIXTURE_TAG,
            ));
        }

        return $this->loadedFixtures[$class];
    }

    /**
     * Returns the array of data fixtures to execute.
     *
     * @param string[] $groups
     *
     * @return FixtureInterface[]
     */
    public function getFixtures(array $groups = []): array
    {
        $fixtures = parent::getFixtures();

        if (empty($groups)) {
            return $fixtures;
        }

        $requiredFixtures = [];
        foreach ($groups as $group) {
            if (! isset($this->groupsFixtureMapping[$group])) {
                continue;
            }

            $requiredFixtures += $this->collectDependencies(...array_keys($this->groupsFixtureMapping[$group]));
        }

        $filteredFixtures = [];
        foreach ($fixtures as $order => $fixture) {
            $fixtureClass = get_class($fixture);
            if (isset($requiredFixtures[$fixtureClass])) {
                $filteredFixtures[$order] = $fixture;
                continue;
            }
        }

        return array_values($filteredFixtures);
    }

    /**
     * Generates an array of the groups and their fixtures
     *
     * @param string[] $groups
     */
    private function addGroupsFixtureMapping(string $className, array $groups): void
    {
        foreach ($groups as $group) {
            $this->groupsFixtureMapping[$group][$className] = true;
        }
    }

    /**
     * Collect any dependent fixtures from the given classes.
     *
     * @psalm-return array<string,true>
     */
    private function collectDependencies(string ...$fixtureClass): array
    {
        $dependencies = [];

        foreach ($fixtureClass as $class) {
            $dependencies[$class] = true;
            $fixture              = $this->getFixture($class);

            if (! $fixture instanceof DependentFixtureInterface) {
                continue;
            }

            $dependencies += $this->collectDependencies(...$fixture->getDependencies());
        }

        return $dependencies;
    }
}
