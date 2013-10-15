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
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use InvalidArgumentException;

/**
 * Get last index of OrderedFixtures
 *
 * @author Miguel Ángel Sánchez Chordi <mangel.snc@gmail.com>
 */
class ListFixturesCommand extends DoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:list')
            ->setDescription('Get a list of all the fixtures without execute it.')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = array();
        foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
            $paths[] = $bundle->getPath().'/DataFixtures/ORM';
        }
		
        $loader = new DataFixturesLoader($this->getContainer());
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }
        $fixtures = $loader->getFixtures();
        if (!$fixtures) {
            throw new InvalidArgumentException(
                sprintf('<error>Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths).'</error>')
            );
        }
		
		$output->writeln(sprintf("Order\tFixture\n----------------------------------------------------------------------"));
		foreach($fixtures as $fixture) {
			if($fixture instanceof OrderedFixtureInterface ) {
				$output->writeln(sprintf("[%d]\t%s", $fixture->getOrder(), get_class($fixture)));
			}else {
				$output->writeln(sprintf("[-]\t%s", get_class($fixture)));
			}
		}

    }
}
