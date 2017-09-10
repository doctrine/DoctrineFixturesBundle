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
class GetLastOrderCommand extends DoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:get-last-order')
            ->setDescription('Get the last index used in OrderedFixtures.')
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

		$max = 0;
		foreach($fixtures as $fixture) {
			if($fixture instanceOf OrderedFixtureInterface) {
				if($fixture->getOrder() > $max){
					$max = $fixture->getOrder();
				}
			}
		}
		$output->writeln(sprintf("<info>Last order index used is: %d</info>", $max));

    }
}
