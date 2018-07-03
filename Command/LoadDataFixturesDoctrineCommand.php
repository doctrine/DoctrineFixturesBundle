<?php


namespace Doctrine\Bundle\FixturesBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Sharding\PoolingShardConnection;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;

/**
 * Load data fixtures from bundles.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class LoadDataFixturesDoctrineCommand extends DoctrineCommand
{
    private $fixturesLoader;

    public function __construct(SymfonyFixturesLoader $fixturesLoader)
    {
        parent::__construct();

        $this->fixturesLoader = $fixturesLoader;
    }

    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:load')
            ->setDescription('Load data fixtures to your database')
            ->addOption('fixtures', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The directory to load data fixtures from.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command loads data fixtures from your application:

  <info>php %command.full_name%</info>

You can also optionally specify the path to fixtures with the <info>--fixtures</info> option:

  <info>php %command.full_name% --fixtures=/path/to/fixtures1 --fixtures=/path/to/fixtures2</info>

Fixtures are services that are tagged with <comment>doctrine.fixture.orm</comment>.

If you want to append the fixtures instead of flushing the database first you can use the <comment>--append</comment> option:

  <info>php %command.full_name%</info> <comment>--append</comment>

By default Doctrine Data Fixtures uses DELETE statements to drop the existing rows from the database.
If you want to use a TRUNCATE statement instead you can use the <comment>--purge-with-truncate</comment> flag:

  <info>php %command.full_name%</info> <comment>--purge-with-truncate</comment>

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ui = new SymfonyStyle($input, $output);

        /** @var $doctrine \Doctrine\Common\Persistence\ManagerRegistry */
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager($input->getOption('em'));

        if (!$input->getOption('append')) {
            $ui->ask('Careful, database will be purged. Do you want to continue y/N ?', false);
        }

        if ($input->getOption('shard')) {
            if (!$em->getConnection() instanceof PoolingShardConnection) {
                throw new \LogicException(sprintf(
                    'Connection of EntityManager "%s" must implement shards configuration.',
                    $input->getOption('em')
                ));
            }

            $em->getConnection()->connect($input->getOption('shard'));
        }

        $dirOrFile = $input->getOption('fixtures');
        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : array($dirOrFile);
        } else {
            /** @var $kernel \Symfony\Component\HttpKernel\KernelInterface */
            $kernel = $this->getApplication()->getKernel();
            $paths = array($kernel->getRootDir().'/DataFixtures');
            foreach ($kernel->getBundles() as $bundle) {
                $paths[] = $bundle->getPath().'/DataFixtures';
            }
        }

        $loader = new DataFixturesLoader($this->getContainer());
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            } elseif (is_file($path)) {
                $loader->loadFromFile($path);
            }
        }
        $fixtures = $loader->getFixtures();
        if (!$fixtures) {
            $ui->error(sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths)));

            return 1;
        }
        $purger = new ORMPurger($em);
        $purger->setPurgeMode($input->getOption('purge-with-truncate') ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($em, $purger);
        $executor->setLogger(function ($message) use ($ui) {
            $ui->text(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $executor->execute($fixtures, $input->getOption('append'));
    }
}
