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

namespace Doctrine\Bundle\FixturesBundle\Console;

use Doctrine\Common\EventSubscriber;
use Doctrine\Fixture\Event\FixtureEvent;
use Doctrine\Fixture\Event\BulkFixtureEvent;
use Doctrine\Fixture\Event\ImportFixtureEventListener;
use Doctrine\Fixture\Event\PurgeFixtureEventListener;
use Doctrine\Fixture\Event\BulkImportFixtureEventListener;
use Doctrine\Fixture\Event\BulkPurgeFixtureEventListener;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract Console Reporter
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
abstract class AbstractConsoleReporter implements
    EventSubscriber,
    ImportFixtureEventListener,
    PurgeFixtureEventListener,
    BulkImportFixtureEventListener,
    BulkPurgeFixtureEventListener
{
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var integer
     */
    protected $currentFixtureListItem;

    /**
     * @var integer
     */
    protected $totalFixtureListItems;

    /**
     * Consructor.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            BulkImportFixtureEventListener::BULK_IMPORT,
            BulkPurgeFixtureEventListener::BULK_PURGE,
            ImportFixtureEventListener::IMPORT,
            PurgeFixtureEventListener::PURGE,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function bulkImport(BulkFixtureEvent $event)
    {
        $this->currentFixtureListItem = 0;
        $this->totalFixtureListItems  = count($event->getFixtureList());

        $this->output->writeln('Importing fixtures...');

        $this->reportBulkOperation($event);
    }

    /**
     * {@inheritdoc}
     */
    public function bulkPurge(BulkFixtureEvent $event)
    {
        $this->currentFixtureListItem = 0;
        $this->totalFixtureListItems  = count($event->getFixtureList());

        $this->output->writeln('Purging fixtures...');

        $this->reportBulkOperation($event);
    }

    /**
     * {@inheritdoc}
     */
    public function import(FixtureEvent $event)
    {
        $this->currentFixtureListItem++;

        $this->reportSingleOperation($event);
    }

    /**
     * {@inheritdoc}
     */
    public function purge(FixtureEvent $event)
    {
        $this->currentFixtureListItem++;

        $this->reportSingleOperation($event);
    }

    /**
     * Reports the execution of a bulk operation.
     *
     * @param \Doctrine\Fixture\Event\BulkFixtureEvent $event
     */
    abstract protected function reportBulkOperation(BulkFixtureEvent $event);

    /**
     * Progress report.
     *
     * @param \Doctrine\Fixture\Event\FixtureEvent $event
     */
    abstract protected function reportSingleOperation(FixtureEvent $event);
}