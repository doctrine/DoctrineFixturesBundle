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
use Symfony\Component\Finder\Finder;
use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\Annotations\Reader;
use InvalidArgumentException;
use ReflectionClass;


class LoadDataFixturesDoctrineCommand extends DoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:load')
            ->setDescription('Load data fixtures to your database.')
            ->addOption('fixtures', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The directory or file to load data fixtures from.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->setHelp(<<<EOT
The <info>doctrine:fixtures:load</info> command loads data fixtures from your bundles:

  <info>./app/console doctrine:fixtures:load</info>

You can also optionally specify the path to fixtures with the <info>--fixtures</info> option:

  <info>./app/console doctrine:fixtures:load --fixtures=/path/to/fixtures1 --fixtures=/path/to/fixtures2</info>

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
        /** @var $reader Reader */
        $reader = $this->getContainer()->get('annotation_reader');

        /** @var $doctrine \Doctrine\Common\Persistence\ManagerRegistry */
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager($input->getOption('em'));

        $fixtures = $input->getOption('fixtures');

        $paths = array();
        $files = array();
        if (empty($fixtures)) {
            foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
                if (file_exists($path = $bundle->getPath().'/DataFixtures/ORM')) {
                    $paths[] = $path;
                }
            }
        }
        else
        {
            foreach ($fixtures as $fixture) {
                if (!file_exists($fixture)) {
                    throw new InvalidArgumentException('Fixture ' . $fixture . ' not found');
                }

                if (is_dir($fixture)) {
                    $paths[] = $fixture;
                }
                else {
                    $files[] = $fixture;
                }
            }
        }

        $fixtures = array();
        $files    = array_merge($files, $this->getFiles($paths));
        foreach ($files as $file)
        {
            $className = $this->getClassName($file);
            $reflection = new ReflectionClass($className);
            $annotation = $reader->getClassAnnotation($reflection, 'Doctrine\Bundle\FixturesBundle\Annotation\Fixture');

            // has any annotation?
            if (null === $annotation) {
                continue;
            }

            // configured properly?
            if (null === $annotation->env) {
                continue;
            }

            if (!in_array($input->getOption('env'), $annotation->env)) {
                continue;
            }

            $instance = new $className;

            if ($reflection->implementsInterface('Symfony\Component\DependencyInjection\ContainerAwareInterface')) {
                $instance->setContainer($this->getContainer());
            }

            $fixtures[$annotation->order . $className] = $instance;
        }

        // sort according order and username
        ksort($fixtures);
        $fixtures = array_values($fixtures);

        $purger = new ORMPurger($em);
        $purger->setPurgeMode($input->getOption('purge-with-truncate') ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($em, $purger);
        $executor->setLogger(function($message) use ($output) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $executor->execute($fixtures, $input->getOption('append'));

    }

    protected function getFiles(array $dirs)
    {
        if (empty($dirs)) {
            return array();
        }

        $finder = new Finder();
        $finder
            ->files()
            ->in($dirs)
            ->ignoreVCS(true)
            ->filter(function($file)
            {
                if (__FILE__ === realpath($file->getPathName())) {
                    return false;
                }

                if (false !== strpos($file->getPathName(), 'Doctrine/Bundle/FixturesBundle/Annotation/Fixture.php')) {
                    return false;
                }

                return false !== strpos(file_get_contents($file->getPathName()), 'Doctrine\Bundle\FixturesBundle\Annotation\Fixture');
            })
        ;

        return array_keys(iterator_to_array($finder));
    }

    protected function getClassName($filename)
    {
        $src = file_get_contents($filename);

        if (!preg_match('/\bnamespace\s+([^;]+);/s', $src, $match)) {
            throw new InvalidArgumentException(sprintf('Namespace could not be determined for file "%s".', $filename));
        }
        $namespace = $match[1];

        if (!preg_match('/\bclass\s+([^\s]+)\s+(?:extends|implements|{)/s', $src, $match)) {
            throw new InvalidArgumentException(sprintf('Could not extract class name from file "%s".', $filename));
        }

        return $namespace.'\\'.$match[1];
    }
}