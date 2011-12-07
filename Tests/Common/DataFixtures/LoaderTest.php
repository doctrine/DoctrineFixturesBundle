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

namespace Doctrine\Bundle\FixturesBundle\Tests\Common\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Tests\TestCase;
use Doctrine\Bundle\FixturesBundle\Tests\Common\ContainerAwareFixture;
use Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader;

class LoaderTest extends TestCase
{
    public function testShouldSetContainerOnContainerAwareFixture()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $loader    = new Loader($container);
        $fixture   = new ContainerAwareFixture();

        $loader->addFixture($fixture);

        $this->assertSame($container, $fixture->container);
    }
}
