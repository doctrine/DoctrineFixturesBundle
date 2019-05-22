<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Service;

use Doctrine\Bundle\FixturesBundle\Exception\NoFixtureServicesFound;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\Persistence\ObjectManager;

class DoctrineFixtureService
{
    /** @var ObjectManager */
    private $em;
    /** @var SymfonyFixturesLoader */
    private $fixturesLoader;

    /** @var int */
    private $purgeMode = ORMPurger::PURGE_MODE_DELETE;
    /** @var bool */
    private $append = false;

    public function __construct(SymfonyFixturesLoader $fixturesLoader, ObjectManager $em)
    {
        $this->fixturesLoader = $fixturesLoader;
        $this->em             = $em;
    }

    /**
     * @throws NoFixtureServicesFound
     */
    public function load(callable $logger) : void
    {
        $fixtures = $this->fixturesLoader->getFixtures();
        if (! $fixtures) {
            throw new NoFixtureServicesFound('Could not find any fixture services to load.');
        }
        $purger = new ORMPurger($this->em);
        $purger->setPurgeMode($this->getPurgeMode());
        $executor = new ORMExecutor($this->em, $purger);
        $executor->setLogger($logger);
        $executor->execute($fixtures, $this->isAppend());
    }

    public function getPurgeMode() : int
    {
        return $this->purgeMode;
    }

    public function setPurgeMode(int $purgeMode) : DoctrineFixtureService
    {
        $this->purgeMode = $purgeMode;

        return $this;
    }

    public function isAppend() : bool
    {
        return $this->append;
    }

    public function setAppend(bool $append) : DoctrineFixtureService
    {
        $this->append = $append;

        return $this;
    }
}
