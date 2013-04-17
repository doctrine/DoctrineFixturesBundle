<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Bundle\FixturesBundle\Tests\EventListener;

use Doctrine\Bundle\FixturesBundle\EventListener\ContainerAwareEventSubscriber;
use Doctrine\Fixture\Event\FixtureEvent;
use Doctrine\Fixture\Event\ImportFixtureEventListener;
use Doctrine\Fixture\Event\PurgeFixtureEventListener;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * ContainerAwareEventSubscriber tests.
 *
 * @group EventListener
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ContainerAwareEventSubscriberTest extends TestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Doctrine\Bundle\FixturesBundle\EventListener\ContainerAwareEventSubscriber
     */
    private $subscriber;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->container  = new ContainerBuilder();
        $this->subscriber = new ContainerAwareEventSubscriber($this->container);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        unset($this->subscriber);
        unset($this->container);

        parent::tearDown();
    }

    public function testGetSubscribedEvents()
    {
        $subscribedEvents = $this->subscriber->getSubscribedEvents();

        $this->assertContains(ImportFixtureEventListener::IMPORT, $subscribedEvents);
        $this->assertContains(PurgeFixtureEventListener::PURGE, $subscribedEvents);
    }

    /**
     * @dataProvider provideDataForImport
     */
    public function testImport($mockClass, $expects)
    {
        $fixture = $this->createFixtureMock($mockClass, $expects);
        $event   = new FixtureEvent($fixture);

        $this->subscriber->import($event);
    }

    public function provideDataForImport()
    {
        return array(
            array('Doctrine\Bundle\FixturesBundle\Tests\MockObject\ContainerAwareFixture', $this->once()),
            array('Doctrine\Fixture\Fixture', $this->never()),
        );
    }

    /**
     * @dataProvider provideDataForPurge
     */
    public function testPurge($mockClass, $expects)
    {
        $fixture = $this->createFixtureMock($mockClass, $expects);
        $event   = new FixtureEvent($fixture);

        $this->subscriber->purge($event);
    }

    public function provideDataForPurge()
    {
        return array(
            array('Doctrine\Bundle\FixturesBundle\Tests\MockObject\ContainerAwareFixture', $this->once()),
            array('Doctrine\Fixture\Fixture', $this->never()),
        );
    }

    private function createFixtureMock($mockClass, $expects)
    {
        $mock = $this
            ->getMockBuilder($mockClass)
            ->disableOriginalConstructor()
            ->getMock();

        if (method_exists($mockClass, 'setContainer')) {
            $mock
                ->expects($expects)
                ->method('setContainer')
                ->with($this->container);
        }

        return $mock;
    }
}