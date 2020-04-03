<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Tests;

use Doctrine\Common\Persistence\ManagerRegistry as DeprecatedManagerRegistry;
use Doctrine\Persistence\ManagerRegistry;
use function interface_exists;

final class DeprecationUtil
{
    public static function getManagerRegistryClass() : string
    {
        return interface_exists(ManagerRegistry::class) ? ManagerRegistry::class : DeprecatedManagerRegistry::class;
    }
}
