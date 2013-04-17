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

namespace Doctrine\Bundle\FixturesBundle\Command;

use Doctrine\Fixture\Executor;
use Doctrine\Fixture\Filter\GroupedFilter;
use Doctrine\Bundle\FixturesBundle\Console\DefaultConsoleReporter;
use Doctrine\Bundle\FixturesBundle\Console\VerboseConsoleReporter;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Execute command.
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
class ExecuteCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:execute')
            ->setDescription('Import data fixtures to your database.')
            ->addOption('group', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Groups to load data fixtures')
            ->addOption('import', null, InputOption::VALUE_NONE, 'Import data fixtures')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Purge existing data fixtures');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $flags     = $this->createFlags($input);
        $executor  = $container->get('doctrine_fixtures.executor');

        // Preparing
        $this->updateFilter($input);
        $this->updateEventManager($output);

        // Executing
        $executor->execute($flags);
    }

    /**
     * Update filter configuration based on provided input.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     */
    protected function updateFilter(InputInterface $input)
    {
        $container = $this->getContainer();
        $filter    = $container->get('doctrine_fixtures.filter');

        // Possibly restrict fixtures by groups
        if (($groupList = $input->getOption('group')) !== null) {
            $filter->addFilter(new GroupedFilter($groupList, true));
        }
    }

    /**
     * Update event manager to include console reporter.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function updateEventManager(OutputInterface $output)
    {
        $container    = $this->getContainer();
        $eventManager = $container->get('doctrine_fixtures.event_manager');
        $reporter     = (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity())
            ? new VerboseConsoleReporter($output)
            : new DefaultConsoleReporter($output);

        // Activating reporter
        $eventManager->addSubscriber($reporter);
    }

    /**
     * Create execution flags.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return integer
     */
    protected function createFlags(InputInterface $input)
    {
        $flags = 0;

        if ($input->getOption('import')) {
            $flags |= Executor::IMPORT;
        }

        if ($input->getOption('purge')) {
            $flags |= Executor::PURGE;
        }

        return $flags;
    }
}
