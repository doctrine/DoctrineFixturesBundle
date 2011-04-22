<?php

namespace Symfony\Bundle\DoctrineFixturesBundle\Tests\Common\DataFixtures;

use Symfony\Bundle\DoctrineFixturesBundle\Tests\TestCase;
use Symfony\Bundle\DoctrineFixturesBundle\Tests\Common\ContainerAwareFixture;
use Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader;

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
