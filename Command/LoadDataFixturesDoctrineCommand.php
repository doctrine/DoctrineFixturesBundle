<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\PurgerFactoryCompilerPass;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Bundle\FixturesBundle\Purger\ORMPurgerFactory;
use Doctrine\Bundle\FixturesBundle\Purger\PurgerFactory;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\DBAL\Sharding\PoolingShardConnection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function assert;
use function implode;
use function sprintf;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Load data fixtures from bundles.
 */
class LoadDataFixturesDoctrineCommand extends DoctrineCommand
{
    /** @var SymfonyFixturesLoader */
    private $fixturesLoader;

    /** @var PurgerFactory[] */
    private $purgerFactories;

    /**
     * @param PurgerFactory[] $purgerFactories
     */
    public function __construct(SymfonyFixturesLoader $fixturesLoader, ?ManagerRegistry $doctrine = null, array $purgerFactories = [])
    {
        if ($doctrine === null) {
            @trigger_error(sprintf(
                'Argument 2 of %s() expects an instance of %s, not passing it will throw a \TypeError in DoctrineFixturesBundle 4.0.',
                __METHOD__,
                ManagerRegistry::class
            ), E_USER_DEPRECATED);
        }

        parent::__construct($doctrine);

        $this->fixturesLoader  = $fixturesLoader;
        $this->purgerFactories = $purgerFactories;
    }

    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:load')
            ->setDescription('Load data fixtures to your database')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
            ->addOption('group', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Only load fixtures that belong to this group')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('purger', null, InputOption::VALUE_REQUIRED, 'The purger to use for this command', 'default')
            ->addOption('purge-exclusions', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'List of database tables to ignore while purging')
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

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ui = new SymfonyStyle($input, $output);

        $em = $this->getDoctrine()->getManager($input->getOption('em'));
        assert($em instanceof EntityManagerInterface);

        if (! $input->getOption('append')) {
            if (! $ui->confirm(sprintf('Careful, database "%s" will be purged. Do you want to continue?', $em->getConnection()->getDatabase()), ! $input->isInteractive())) {
                return 0;
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
                PurgerFactoryCompilerPass::PURGER_FACTORY_TAG
            ));
            $factory = new ORMPurgerFactory();
        } else {
            $factory = $this->purgerFactories[$input->getOption('purger')];
        }

        $purger   = $factory->createForEntityManager(
            $input->getOption('em'),
            $em,
            $input->getOption('purge-exclusions'),
            $input->getOption('purge-with-truncate')
        );
        $executor = new ORMExecutor($em, $purger);
        $executor->setLogger(static function ($message) use ($ui): void {
            $ui->text(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $executor->execute($fixtures, $input->getOption('append'));

        return 0;
    }
}
