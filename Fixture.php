<?php

/*
 * This file is part of the Doctrine Fixtures Bundle.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\FixturesBundle;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Base class designed for data fixtures so they don't have to extend and
 * implement different classes/interfaces according to their needs.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
abstract class Fixture extends AbstractFixture implements ORMFixtureInterface
{
}
