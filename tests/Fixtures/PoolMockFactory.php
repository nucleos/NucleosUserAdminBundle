<?php

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserAdminBundle\Tests\Fixtures;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\DependencyInjection\Container;

final class PoolMockFactory
{
    /**
     * @param array<string, AdminInterface> $adminServiceIds
     */
    public static function create(array $adminServiceIds = []): Pool
    {
        $container = new Container();

        foreach ($adminServiceIds as $id => $service) {
            $container->set($id, $service);
        }

        return new Pool($container, array_keys($adminServiceIds));
    }
}
