<?php

namespace Doctrine\Bundle\FixturesBundle\Tests\Fixtures\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Tests\Fixtures\Entity\CheckIt;

class CheckLoad implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $userAdmin = new CheckIt();
        $userAdmin->setEmail('CheckLoad@got.me');

        $manager->persist($userAdmin);
        $manager->flush();
    }
}
