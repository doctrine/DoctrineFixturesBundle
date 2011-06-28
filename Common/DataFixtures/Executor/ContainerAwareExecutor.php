<?php

namespace Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Executor;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\ORM\EntityManager;

class ContainerAwareExecutor extends ORMExecutor
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Default constructor.
     *
     * @param EntityManager $em
     * @param PurgerInterface $purger
     * @param ContainerInterface $container
     */
    public function __construct(EntityManager $em, PurgerInterface $purger, ContainerInterface $container)
    {
        parent::__construct($em, $purger);

        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @see Doctrine\Common\DataFixtures\Executor.AbstractExecutor::load()
     * {@inheritdoc}
     */
    public function load($manager, FixtureInterface $fixture)
    {
        if ($fixture instanceof ContainerAwareInterface) {
            $fixture->setContainer($this->getContainer());
        }

        parent::load($manager, $fixture);
    }
}