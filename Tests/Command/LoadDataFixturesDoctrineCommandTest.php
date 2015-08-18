<?php

namespace Doctrine\Bundle\FixturesBundle\Tests\Command;

use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class LoadDataFixturesDoctrineCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $kernel;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $emMock;

    /**
     * @dataProvider fixturesProvider
     * @param string $fixtures
     * @param array $expectLoad
     */
    public function testFixtureLoad($fixtures, array $expectLoad = array('ORM\CheckLoad'))
    {
        $container = $this->getMockContainer();

        $app = new Application($this->kernel);
        $app->add(new LoadDataFixturesDoctrineCommand());

        /** @var LoadDataFixturesDoctrineCommand $command */
        $command = $app->find('doctrine:fixtures:load');
        $command->setContainer($container);

        $this->emMock->expects($this->exactly(count($expectLoad)))
            ->method('persist');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            '--fixtures' => $fixtures,
            '--append' => '',
        ), array(
            'interactive' => false,
        ));

        $display = $commandTester->getDisplay();
        foreach ($expectLoad as $shouldBeLoaded) {
            $this->assertContains($shouldBeLoaded, $display);
        }
    }

    /**
     * @return array
     */
    public function fixturesProvider()
    {
        return array(
            array(__DIR__ . '/../Fixtures/DataFixtures/ORM', array('ORM\CheckLoad', 'ORM\CheckLoad2', 'ORM\Product\CheckLoad3')),
            array('@AcmeTestBundle', array('ORM\CheckLoad', 'ORM\CheckLoad2', 'ORM\Product\CheckLoad3')),
            array('@AcmeBundle/Product', array('ORM\Product\CheckLoad3')),
        );
    }

    /**
     * @depends testFixtureLoad
     * @expectedException \InvalidArgumentException
     */
    public function testWrongPathGivesNoFixturesFound()
    {
        $this->testFixtureLoad('/nonexistant', array());
    }

    /**
     * @depends testFixtureLoad
     * @expectedException \InvalidArgumentException
     */
    public function testWrongBundleGivesNoFixturesFound()
    {
        $this->testFixtureLoad('@nonexistant', array());
    }

    private function getMockContainer()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())
            ->method('getKernel')
            ->will($this->returnValue($this->getMockKernel()));

        $doctrineMock = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $container->expects($this->any())
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrineMock));

        $this->emMock = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $doctrineMock->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->emMock));

        $doctrineEventMock = $this->getMock('Doctrine\Common\EventManager');
        $this->emMock->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($doctrineEventMock));

        $em = $this->emMock;
        $this->emMock->expects($this->any())
            ->method('transactional')
            ->will($this->returnCallback(function ($callable) use ($em) {
                call_user_func($callable, $em);
            }));

        return $container;
    }

    private function getMockKernel()
    {
        $self = $this;
        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $this->kernel->expects($this->any())
            ->method('getBundle')
            ->will($this->returnCallback(function ($bundle) use ($self) {

                if ('nonexistant' === $bundle) {
                    throw new \InvalidArgumentException('Mimic Kernel');
                }

                return $self->getBundle();
            }));

        $this->kernel->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue(array($this->getBundle())));

        return $this->kernel;
    }

    public function getBundle($path = '/../Fixtures')
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue(realpath(__DIR__ . $path)));

        return $bundle;
    }
}
