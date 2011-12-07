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

namespace Doctrine\Bundle\FixturesBundle\Tests;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Doctrine\Common\DataFixtures\Loader')) {
            $this->markTestSkipped('Doctrine Data Fixtures is not available.');
        }
    }
}
