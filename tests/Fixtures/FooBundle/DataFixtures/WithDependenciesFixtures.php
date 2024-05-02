<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class WithDependenciesFixtures implements ORMFixtureInterface, DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // ...
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [OtherFixtures::class];
    }

    /**
     * {@inheritDoc}
     */
    public static function getGroups(): array
    {
        return ['groupWithDependencies', 'fulfilledDependencyGroup'];
    }
}
