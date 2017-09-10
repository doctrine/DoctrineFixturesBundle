<?php

/*
 * This file is part of the Doctrine Fixtures Bundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\FixturesBundle\Loader;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

class DatabasePopulator
{
    /**
     * @var FixtureLoader
     */
    protected $fixtureLoader;

    /**
     * @var ORMPurger
     */
    protected $purger;

    /**
     * @var mixed
     */
    protected $dirOrFile;

    /**
     * @var bool
     */
    protected $purgeWithTruncate = false;

    /**
     * @var bool
     */
    protected $append = false;

    /**
     * @param FixtureLoader $fixtureLoader
     * @param ORMPurger $purger
     */
    public function __construct(FixtureLoader $fixtureLoader, ORMPurger $purger)
    {
        $this->fixtureLoader = $fixtureLoader;
        $this->purger = $purger;
    }

    /**
     * @param mixed $dirOrFile
     */
    public function setDirOrFile($dirOrFile)
    {
        $this->dirOrFile = $dirOrFile;
    }

    /**
     * @param boolean $purgeWithTruncate
     */
    public function setPurgeWithTruncate($purgeWithTruncate)
    {
        $this->purgeWithTruncate = $purgeWithTruncate;
    }

    /**
     * @param boolean $append
     */
    public function setAppend($append)
    {
        $this->append = $append;
    }

    /**
     * @param EntityManagerInterface $em
     * @param ORMExecutor $executor
     * @param null|\Psr\Log\LoggerInterface|\Closure $logger
     */
    public function load(EntityManagerInterface $em, ORMExecutor $executor, $logger = null)
    {
        $fixtures = $this->fixtureLoader->getFixtures($this->dirOrFile);
        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Could not find any fixtures to load in: %s',
                    "\n\n- ".implode("\n- ", $this->fixtureLoader->getPaths($this->dirOrFile))
                )
            );
        }
        $this->purger->setEntityManager($em);
        if ($this->purgeWithTruncate) {
            $this->purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        }
        $executor->setPurger($this->purger);
        if ($logger) {
            $executor->setLogger($logger);
        }
        $executor->execute($fixtures, $this->append);
    }
}
