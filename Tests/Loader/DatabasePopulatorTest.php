<?php

namespace Doctrine\Bundle\FixtureBundle\Tests\Loader;

use Doctrine\Bundle\FixturesBundle\Loader\DatabasePopulator;

class DatabasePopulatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Could not find any fixtures to load in:
     */
    public function throws_exception_if_no_fixtures_was_found()
    {
        $ormPurgerMock = $this->getMockBuilder('Doctrine\Common\DataFixtures\Purger\ORMPurger')
            ->disableOriginalConstructor()->getMock();
        $emMock = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $executorMock = $this->getMockBuilder('Doctrine\Common\DataFixtures\Executor\ORMExecutor')
            ->disableOriginalConstructor()->getMock();

        $fixtureLoaderMock = $this->getMockBuilder('Doctrine\Bundle\FixturesBundle\Loader\FixtureLoader')
            ->disableOriginalConstructor()->getMock();
        $fixtureLoaderMock->expects($this->once())->method('getFixtures')->willReturn(array());
        $fixtureLoaderMock->expects($this->once())->method('getPaths')->willReturn(array());

        $databasePopulator = new DatabasePopulator($fixtureLoaderMock, $ormPurgerMock);
        $databasePopulator->load($emMock, $executorMock);
    }

    /**
     * @test
     */
    public function should_execute()
    {
        $ormPurgerMock = $this->getMockBuilder('Doctrine\Common\DataFixtures\Purger\ORMPurger')
            ->disableOriginalConstructor()->getMock();
        $emMock = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $executorMock = $this->getMockBuilder('Doctrine\Common\DataFixtures\Executor\ORMExecutor')
            ->disableOriginalConstructor()->getMock();
        $executorMock->expects($this->once())->method('execute');

        $fixtureLoaderMock = $this->getMockBuilder('Doctrine\Bundle\FixturesBundle\Loader\FixtureLoader')
            ->disableOriginalConstructor()->getMock();
        $fixtureLoaderMock->expects($this->once())->method('getFixtures')->willReturn(array('Foo'));

        $databasePopulator = new DatabasePopulator($fixtureLoaderMock, $ormPurgerMock);
        $databasePopulator->load($emMock, $executorMock);
    }
}
