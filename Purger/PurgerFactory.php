<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Purger;

use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\ORM\EntityManagerInterface;

interface PurgerFactory
{
    public function createForEntityManager(?string $emName, EntityManagerInterface $em, array $excluded = [], bool $purgeWithTruncate = false) : PurgerInterface;
}
