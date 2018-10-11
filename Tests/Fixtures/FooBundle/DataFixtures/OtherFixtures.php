<?php

namespace Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class OtherFixtures implements ORMFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager)
    {
        // ...
    }

    public static function getGroups()
    {
        return ['staging'];
    }
}
