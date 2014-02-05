<?php

/*
 * This file is part of the Doctrine Fixtures Bundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\FixturesBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use InvalidArgumentException;

/**
 * Load data fixtures from bundles.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class LoadDataFixturesDoctrineCommand extends DoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:load')
            ->setDescription('Load data fixtures to your database.')
            ->addOption('fixtures', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The directory to load data fixtures from.')
            ->addOption('fixture-classes', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The fixtures classes to load data fixtures from.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->setHelp(<<<EOT
The <info>doctrine:fixtures:load</info> command loads data fixtures from your bundles:

  <info>./app/console doctrine:fixtures:load</info>

You can also optionally specify the path to fixtures with the <info>--fixtures</info> option:

  <info>./app/console doctrine:fixtures:load --fixtures=/path/to/fixtures1 --fixtures=/path/to/fixtures2</info>

You can also optionally specify the classes of fixtures with the <info>--fixture-classes</info> option
(replace <comment>\</comment> in class name with <comment>/</comment>):

  <info>./app/console doctrine:fixtures:load --fixture-classes=ClassName/Of/Fixtures1 --fixture-classes=ClassName/Of/Fixtures2</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>./app/console doctrine:fixtures:load --append</info>

By default Doctrine Data Fixtures uses DELETE statements to drop the existing rows from
the database. If you want to use a TRUNCATE statement instead you can use the <info>--purge-with-truncate</info> flag:

  <info>./app/console doctrine:fixtures:load --purge-with-truncate</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $doctrine \Doctrine\Common\Persistence\ManagerRegistry */
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager($input->getOption('em'));

        if ($input->isInteractive() && !$input->getOption('append')) {
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation($output, '<question>Careful, database will be purged. Do you want to continue Y/N ?</question>', false)) {
                return;
            }
        }

        $dirOrFile = $input->getOption('fixtures');
        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : array($dirOrFile);
        } else {
            $paths = array();
        }

        $classNames = $input->getOption('fixture-classes');
        if ($classNames) {
            $classNames = is_array($classNames) ? $classNames : array($classNames);
            $classNames = array_map(function ($className) {
                return str_replace('/', '\\', $className);
            }, $classNames);
        } else {
            $classNames = array();
        }

        if (!$paths && !$classNames) {
            foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
                $paths[] = $bundle->getPath().'/DataFixtures/ORM';
            }
        }

        $loader = new DataFixturesLoader($this->getContainer());

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        foreach ($classNames as $className) {
            if (!class_exists($className)) {
                throw new InvalidArgumentException(
                    sprintf('Could not load class %s', $className)
                );
            }
            if (!$loader->isTransient($className)) {
                $loader->addFixture(new $className);
            }
        }

        $fixtures = $loader->getFixtures();
        if (!$fixtures) {
            $message = array();
            if ($paths) {
                $message[] = sprintf('in paths: %s', "\n\n- ".implode("\n- ", $paths));
            }
            if ($classNames) {
                $message[] = sprintf('in classes: %s', "\n\n- ".implode("\n- ", $classNames));
            }
            throw new InvalidArgumentException(
                sprintf('Could not find any fixtures to load %s', implode("\n\nand ", $message))
            );
        }
        $purger = new ORMPurger($em);
        $purger->setPurgeMode($input->getOption('purge-with-truncate') ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($em, $purger);
        $executor->setLogger(function($message) use ($output) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $executor->execute($fixtures, $input->getOption('append'));
    }
}
