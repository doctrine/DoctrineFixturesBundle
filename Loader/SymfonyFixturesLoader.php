<?php

/*
 * This file is part of the Doctrine Fixtures Bundle.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\FixturesBundle\Loader;

use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Loader;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;

/**
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
final class SymfonyFixturesLoader extends ContainerAwareLoader
{
    private $loadedFixtures = [];

    private $groupsFixtureMapping = [];

    /**
     * @internal
     */
    public function addFixtures(array $fixtures)
    {
        // Store all loaded fixtures so that we can resolve the dependencies correctly.
        foreach ($fixtures as $fixture) {
            $class = get_class($fixture['fixture']);
            $this->loadedFixtures[$class] = $fixture['fixture'];
            $this->addGroupsFixtureMapping($class, $fixture['groups']);
        }

        // Now load all the fixtures
        foreach ($this->loadedFixtures as $fixture) {
            $this->addFixture($fixture);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addFixture(FixtureInterface $fixture)
    {
        $class = get_class($fixture);
        if (!isset($this->loadedFixtures[$class])) {
            $this->loadedFixtures[$class] = $fixture;
        }

        // see https://github.com/doctrine/data-fixtures/pull/274
        // this is to give a clear error if you do not have this version
        if (!method_exists(Loader::class, 'createFixture')) {
            $this->checkForNonInstantiableFixtures($fixture);
        }

        parent::addFixture($fixture);
    }

    /**
     * Overridden to not allow new fixture classes to be instantiated.
     */
    protected function createFixture($class)
    {
        /*
         * We don't actually need to create the fixture. We just
         * return the one that already exists.
         */

        if (!isset($this->loadedFixtures[$class])) {
            throw new \LogicException(sprintf(
                'The "%s" fixture class is trying to be loaded, but is not available. Make sure this class is defined as a service and tagged with "%s".', $class, FixturesCompilerPass::FIXTURE_TAG
            ));
        }

        return $this->loadedFixtures[$class];
    }

    /**
     * Returns the array of data fixtures to execute.
     *
     * @param array $groups
     *
     * @return array $fixtures
     */
    public function getFixtures(array $groups = [])
    {
        $fixtures = parent::getFixtures();

        if (!empty($groups)) {
            $filteredFixtures = [];
            foreach ($fixtures as $key => $fixture) {
                foreach ($groups as $group) {
                    if (isset($this->groupsFixtureMapping[$group][get_class($fixture)])) {
                        $filteredFixtures[$key] = $fixture;
                    }
                }
            }
            $fixtures = $filteredFixtures;
        }

        return $fixtures;
    }

    /**
     * Generates an array of the groups and their fixtures
     *
     * @param string $className
     * @param array $groups
     *
     * @return void
     */
    public function addGroupsFixtureMapping($className, array $groups)
    {
        foreach ($groups as $group) {
            $this->groupsFixtureMapping[$group][$className] = true;
        }
    }

    /**
     * For doctrine/data-fixtures 1.2 or lower, this detects an unsupported
     * feature with DependentFixtureInterface so that we can throw a
     * clear exception.
     *
     * @param FixtureInterface $fixture
     * @throws \Exception
     */
    private function checkForNonInstantiableFixtures(FixtureInterface $fixture)
    {
        if (!$fixture instanceof DependentFixtureInterface) {
            return;
        }

        foreach ($fixture->getDependencies() as $dependency) {
            if (!class_exists($dependency)) {
                continue;
            }

            if (!method_exists($dependency, '__construct')) {
                continue;
            }

            $reflMethod = new \ReflectionMethod($dependency, '__construct');
            foreach ($reflMethod->getParameters() as $param) {
                if (!$param->isOptional()) {
                    throw new \LogicException(sprintf('The getDependencies() method returned a class (%s) that has required constructor arguments. Upgrade to "doctrine/data-fixtures" version 1.3 or higher to support this.', $dependency));
                }
            }
        }
    }
}
