<?php

namespace Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class WithDependenciesFixtures implements ORMFixtureInterface, DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager)
    {
        // ...
    }

    public function getDependencies()
    {
        return [
            OtherFixtures::class,
        ];
    }

    public static function getGroups()
    {
        return ['missingDependencyGroup', 'fulfilledDependencyGroup'];
    }
}
