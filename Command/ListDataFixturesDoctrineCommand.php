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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

/**
 * List data fixtures from bundles.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class ListDataFixturesDoctrineCommand extends DoctrineCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:list')
            ->setDescription('List available data fixtures.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command list data fixtures from your bundles:

  <info>php %command.full_name%</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
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
            $output->write(sprintf('<error>Could not find any fixtures in: %s</error>', "\n\n- ".implode("\n- ", $paths)));
        } else {
            $maxClassWidth = 0;
            $maxOrderWidth = 0;
            $cachedFixtures = array();

            foreach ($fixtures as $fixture) {
                $class = get_class($fixture);
                $order = $fixture instanceof OrderedFixtureInterface ? $fixture->getOrder() : 'default';

                $classWidth = strlen($class);
                if ($classWidth > $maxClassWidth) {
                    $maxClassWidth = $classWidth;
                }

                $orderWith = strlen($order);
                if ($orderWith > $maxOrderWidth) {
                    $maxOrderWidth = $orderWith;
                }

                $cachedFixtures[] = array('class' => $class, 'order' => $order);
            }

            $formatTitle = '%-'.($maxClassWidth + 19).'s | %-'.($maxOrderWidth + 19).'s';
            $format = '%-'.$maxClassWidth.'s | %-'.$maxOrderWidth.'s';

            $output->writeln(sprintf($formatTitle, '<comment>Fixture</comment>', '<comment>Order</comment>'));

            foreach ($cachedFixtures as $cachedFixture) {
                $output->writeln(sprintf($format, $cachedFixture['class'], $cachedFixture['order']));
            }
        }
    }
}
