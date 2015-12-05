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

namespace Doctrine\Bundle\FixturesBundle\Loader;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

class FixtureLoader
{
    /**
     * @var ContainerAwareLoader
     */
    protected $containerAwareLoader;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param ContainerAwareLoader $containerAwareLoader
     * @param KernelInterface $kernel
     */
    public function __construct(ContainerAwareLoader $containerAwareLoader, KernelInterface $kernel)
    {
        $this->containerAwareLoader = $containerAwareLoader;
        $this->kernel = $kernel;
    }

    /**
     * @param mixed $dirOrFile
     *
     * @return array
     */
    public function getFixtures($dirOrFile)
    {
        foreach ($this->getPaths($dirOrFile) as $path) {
            if (is_dir($path)) {
                $this->containerAwareLoader->loadFromDirectory($path);
            } elseif (is_file($path)) {
                $this->containerAwareLoader->loadFromFile($path);
            }
        }

        return $this->containerAwareLoader->getFixtures();
    }

    /**
     * @param mixed $dirOrFile
     *
     * @return string[]
     */
    public function getPaths($dirOrFile)
    {
        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : array($dirOrFile);
        } else {
            $paths = array();
            foreach ($this->kernel->getBundles() as $bundle) {
                $paths[] = $bundle->getPath().'/DataFixtures/ORM';
            }
        }

        return $paths;
    }
}
