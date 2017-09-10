<?php

/*
 * This file is part of the Doctrine Fixtures Bundle.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\FixturesBundle;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Fixture class that loads no data but it's needed for Doctrine. See the
 * getDependencies() method in the base Fixture class for details.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @internal
 */
final class EmptyFixture implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
    }
}
