<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Loader;

use Doctrine\Common\DataFixtures\Loader;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;

use function class_exists;

if (class_exists(ContainerAwareLoader::class)) {
    /** @internal */
    abstract class SymfonyBridgeLoader extends ContainerAwareLoader
    {
    }
} else {
    /** @internal */
    abstract class SymfonyBridgeLoader extends Loader
    {
    }
}
