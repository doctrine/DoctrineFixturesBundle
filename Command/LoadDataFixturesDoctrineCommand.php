<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Bundle\FixturesBundle\Exception\NoFixtureServicesFound;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Bundle\FixturesBundle\Service\DoctrineFixtureService;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Sharding\PoolingShardConnection;
use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function sprintf;

/**
 * Load data fixtures from bundles.
 */
class LoadDataFixturesDoctrineCommand extends DoctrineCommand
{
    /** @var SymfonyFixturesLoader */
    private $fixturesLoader;

    public function __construct(SymfonyFixturesLoader $fixturesLoader)
    {
        parent::__construct();

        $this->fixturesLoader = $fixturesLoader;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:load')
            ->setDescription('Load data fixtures to your database')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
            ->addOption('group', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Only load fixtures that belong to this group')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->setHelp(<<<EOT
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

EOT
            );
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ui     = new SymfonyStyle($input, $output);
        $logger = static function ($message) use ($ui) : void {
            $ui->text(sprintf('  <comment>></comment> <info>%s</info>', $message));
        };

        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        $em       = $doctrine->getManager($input->getOption('em'));

        if (! $input->getOption('append')) {
            if (! $ui->confirm(sprintf('Careful, database "%s" will be purged. Do you want to continue?', $em->getConnection()->getDatabase()), ! $input->isInteractive())) {
                return;
            }
        }

        if ($input->getOption('shard')) {
            if (! $em->getConnection() instanceof PoolingShardConnection) {
                throw new LogicException(sprintf(
                    'Connection of EntityManager "%s" must implement shards configuration.',
                    $input->getOption('em')
                ));
            }

            $em->getConnection()->connect($input->getOption('shard'));
        }

        $doctrineFixtureService = new DoctrineFixtureService($this->fixturesLoader, $em);
        $doctrineFixtureService->setPurgeMode($input->getOption('purge-with-truncate') ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);
        $doctrineFixtureService->setAppend($input->getOption('append'));
        try {
            $doctrineFixtureService->load($logger);
        } catch (NoFixtureServicesFound $e) {
            $ui->error('Could not find any fixture services to load.');

            return 1;
        }
    }
}
