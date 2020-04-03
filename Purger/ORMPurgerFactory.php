<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Purger;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class ORMPurgerFactory implements PurgerFactory
{
    public function createForEntityManager(?string $emName, EntityManagerInterface $em, array $excluded = [], bool $purgeWithTruncate = false) : PurgerInterface
    {
        $purger = new ORMPurger($em, $excluded);
        $purger->setPurgeMode($purgeWithTruncate ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);

        return $purger;
    }
}
