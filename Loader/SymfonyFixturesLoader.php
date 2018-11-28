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

    /**
     * @internal
     */
    public function addFixtures(array $fixtures)
    {
        // Store all loaded fixtures so that we can resolve the dependencies correctly.
        foreach ($fixtures as $fixture) {
            $this->loadedFixtures[get_class($fixture)] = $fixture;
        }

        // Now load all the fixtures
        foreach ($this->loadedFixtures as $fixture) {
            $this->addFixture($fixture);
        }
    }

    public function addFixture(FixtureInterface $fixture)
    {
        $class = get_class($fixture);
        if (!isset($this->loadedFixtures[$class])) {
            $this->loadedFixtures[$class] = $fixture;
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
}
