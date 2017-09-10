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

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List fixtures from bundles.
 *
 * @author miholeus <me@miholeus.com>
 */
class ListFixturesDoctrineCommand extends AbstractFixturesCommand
{
    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:list')
            ->setDescription('Shows list of available fixtures.')
            ->addOption('fixtures', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The directory to load data fixtures from.')
            ->addOption('order', null, InputOption::VALUE_OPTIONAL, 'Find fixture by specified number if OrderedFixtureInterface is implemented')
            ->setHelp(<<<EOT
The <info>doctrine:fixtures:list</info> command shows data fixtures from your bundles:

  <info>./app/console doctrine:fixtures:list</info>

You can also optionally specify the path to fixtures with the <info>--fixtures</info> option:

  <info>./app/console doctrine:fixtures:list --fixtures=/path/to/fixtures1 --fixtures=/path/to/fixtures2</info>

If you want to view only one fixture with specified order you can use the <info>--order</info> option:

  <info>./app/console doctrine:fixtures:list --order=number</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dirOrFile = $input->getOption('fixtures');
        $order = $input->getOption('order');

        $this->setLogger(function($message) use ($output){
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $fixtures = $this->getFixtures($dirOrFile);

        foreach ($fixtures as $fixture) {
            $prefix = '';
            if ($fixture instanceof OrderedFixtureInterface) {
                if ($order && $order != $fixture->getOrder()) {// if order is set, filter by order
                    continue;
                }
                $prefix = sprintf('[%d] ',$fixture->getOrder());
            }
            $this->log('Fixture ' . $prefix . get_class($fixture));
        }
    }
}