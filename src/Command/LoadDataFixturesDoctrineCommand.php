<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\PurgerFactoryCompilerPass;
use Doctrine\Bundle\FixturesBundle\Loader\FixturesProvider;
use Doctrine\Bundle\FixturesBundle\Purger\ORMPurgerFactory;
use Doctrine\Bundle\FixturesBundle\Purger\PurgerFactory;
use Doctrine\Common\DataFixtures\Executor\DryRunORMExecutor;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\AbstractLogger;
use Stringable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function assert;
use function implode;
use function sprintf;

/**
 * Load data fixtures from bundles.
 */
final class LoadDataFixturesDoctrineCommand extends DoctrineCommand
{
    /** @param PurgerFactory[] $purgerFactories */
    public function __construct(
        private FixturesProvider $fixturesLoader,
        ManagerRegistry $doctrine,
        /** @var array<string, ORMPurgerFactory> $purgerFactories */
        private array $purgerFactories = [],
    ) {
        parent::__construct($doctrine);
    }

    protected function configure(): void
    {
        $this
            ->setName('doctrine:fixtures:load')
            ->setDescription('Load data fixtures to your database')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Load the fixtures as a dry run.')
            ->addOption('group', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Only load fixtures that belong to this group')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('purger', null, InputOption::VALUE_REQUIRED, 'The purger to use for this command', 'default')
            ->addOption('purge-exclusions', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'List of database tables to ignore while purging')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->setHelp(<<<'EOT'
                The <info>%command.name%</info> command loads data fixtures from your application:
                
                  <info>php %command.full_name%</info>
                
                Fixtures are services that are tagged with <comment>doctrine.fixture.orm</comment>.
                
                If you want to append the fixtures instead of flushing the database first you can use the <comment>--append</comment> option:
                
                  <info>php %command.full_name%</info> <comment>--append</comment>
                
                By default Doctrine Data Fixtures uses DELETE statements to drop the existing rows from the database.
                If you want to use a TRUNCATE statement instead you can use the <comment>--purge-with-truncate</comment> flag:
                
                  <info>php %command.full_name%</info> <comment>--purge-with-truncate</comment>
                
                To execute only fixtures that live in a certain group, use:
                
                  <info>php %command.full_name%</info> <comment>--group=group1</comment>

                You can also load the fixtures as a <comment>--dry-run</comment>:

                  <info>php %command.full_name% --dry-run</info>
                
                EOT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);

        $em = $this->getDoctrine()->getManager($input->getOption('em'));
        assert($em instanceof EntityManagerInterface);

        if (! $input->getOption('dry-run') && ! $input->getOption('append')) {
            if (! $ui->confirm(sprintf('Careful, database "%s" will be purged. Do you want to continue?', $em->getConnection()->getDatabase()), ! $input->isInteractive())) {
                return 0;
            }
        }

        $groups   = $input->getOption('group');
        $fixtures = $this->fixturesLoader->getFixtures($groups);
        if (! $fixtures) {
            $message = 'Could not find any fixture services to load';

            if (! empty($groups)) {
                $message .= sprintf(' in the groups (%s)', implode(', ', $groups));
            }

            $ui->error($message . '.');

            return 1;
        }

        if (! isset($this->purgerFactories[$input->getOption('purger')])) {
            $ui->warning(sprintf(
                'Could not find purger factory with alias "%1$s", using default purger. Did you forget to register the %2$s implementation with tag "%3$s" and alias "%1$s"?',
                $input->getOption('purger'),
                PurgerFactory::class,
                PurgerFactoryCompilerPass::PURGER_FACTORY_TAG,
            ));
            $factory = new ORMPurgerFactory();
        } else {
            $factory = $this->purgerFactories[$input->getOption('purger')];
        }

        $purger = $factory->createForEntityManager(
            $input->getOption('em'),
            $em,
            $input->getOption('purge-exclusions'),
            $input->getOption('purge-with-truncate'),
        );

        if ($input->getOption('dry-run')) {
            $ui->text('  <comment>(dry-run)</comment>');
            $executor = new DryRunORMExecutor($em, $purger);
        } else {
            $executor = new ORMExecutor($em, $purger);
        }

        $executor->setLogger(new class ($ui) extends AbstractLogger {
            public function __construct(private SymfonyStyle $ui)
            {
            }

            /** {@inheritDoc} */
            public function log(mixed $level, string|Stringable $message, array $context = []): void
            {
                $this->ui->text(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }

            /** @deprecated to be removed when dropping support for doctrine/data-fixtures <1.8 */
            public function __invoke(string $message): void
            {
                $this->log(0, $message);
            }
        });

        $executor->execute($fixtures, $input->getOption('append'));

        return 0;
    }
}
