<?php

namespace Doctrine\Bundle\FixtureBundle\Tests\Loader;

use Doctrine\Bundle\FixturesBundle\Loader\FixtureLoader;

class FixtureLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function should_return_list_from_bundles_if_nothing_specified()
    {
        $bundleMock = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundleMock->expects($this->once())->method('getPath')->willReturn('AppBundle');
        $kernelMock = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $kernelMock->expects($this->once())->method('getBundles')->willReturn(array($bundleMock));
        $containerAwareLoaderMock = $this->getMockBuilder('Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader')
            ->disableOriginalConstructor()->getMock();

        $fixtureLoader = new FixtureLoader($containerAwareLoaderMock, $kernelMock);
        $paths = $fixtureLoader->getPaths(null);
        $this->assertEquals(array('AppBundle/DataFixtures/ORM'), $paths);
    }
}
