<?php

namespace Doctrine\Bundle\FixturesBundle\Service;

use Doctrine\Bundle\FixturesBundle\Exception\NoFixtureServicesFoundException;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\Persistence\ObjectManager;

class DoctrineFixtureService
{
    /**
     * @var ObjectManager
     */
    private $em;
    /**
     * @var SymfonyFixturesLoader
     */
    private $fixturesLoader;

    /**
     * @var int
     */
    private $purgeMode = ORMPurger::PURGE_MODE_DELETE;
    /**
     * @var bool
     */
    private $append = false;

    /**
     * DoctrineFixtureService constructor.
     * @param SymfonyFixturesLoader $fixturesLoader
     * @param ObjectManager $em
     */
    public function __construct(SymfonyFixturesLoader $fixturesLoader, ObjectManager $em)
    {
        $this->fixturesLoader = $fixturesLoader;
        $this->em = $em;
    }

    /**
     * @param $logger
     * @throws NoFixtureServicesFoundException
     */
    public function load($logger)
    {
        $fixtures = $this->fixturesLoader->getFixtures();
        if (!$fixtures) {
            throw new NoFixtureServicesFoundException('Could not find any fixture services to load.');
        }
        $purger = new ORMPurger($this->em);
        $purger->setPurgeMode($this->getPurgeMode());
        $executor = new ORMExecutor($this->em, $purger);
        $executor->setLogger($logger);
        $executor->execute($fixtures, $this->isAppend());
    }

    /**
     * @return int
     */
    public function getPurgeMode()
    {
        return $this->purgeMode;
    }

    /**
     * @param int $purgeMode
     * @return DoctrineFixtureService
     */
    public function setPurgeMode($purgeMode)
    {
        $this->purgeMode = $purgeMode;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAppend()
    {
        return $this->append;
    }

    /**
     * @param bool $append
     * @return DoctrineFixtureService
     */
    public function setAppend($append)
    {
        $this->append = $append;

        return $this;
    }
}