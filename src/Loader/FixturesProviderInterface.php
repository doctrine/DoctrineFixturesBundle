<?php

declare(strict_types=1);

namespace Doctrine\Bundle\FixturesBundle\Loader;

use Doctrine\Common\DataFixtures\FixtureInterface;

interface FixturesProvider
{
    /**
     * @param string[] $groups
     *
     * @return FixtureInterface[]
     */
    public function getFixtures(array $groups = []): array;
}
