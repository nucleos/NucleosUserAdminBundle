<?php

declare(strict_types=1);

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserAdminBundle\Tests\Admin\Entity;

use Nucleos\UserAdminBundle\Admin\Entity\GroupAdmin;
use Nucleos\UserBundle\Model\Group;
use Nucleos\UserBundle\Model\GroupManagerInterface;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Controller\CRUDController;

final class GroupAdminTest extends TestCase
{
    public function testInstance(): void
    {
        $admin = new GroupAdmin(
            'admin.group',
            Group::class,
            CRUDController::class,
            $this->createMock(GroupManagerInterface::class)
        );

        static::assertNotEmpty($admin);
    }
}
