<?php

namespace Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class OtherFixtures implements ORMFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // ...
    }
}
