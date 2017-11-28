<?php

namespace Doctrine\Bundle\FixturesBundle\Tests\Fixtures\FooBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class RequiredConstructorArgsFixtures implements ORMFixtureInterface
{
    public function __construct($fooRequiredArg)
    {

    }

    public function load(ObjectManager $manager)
    {
        // ...
    }
}
