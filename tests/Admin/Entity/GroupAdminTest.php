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
use Nucleos\UserBundle\Model\GroupManager;
use PHPUnit\Framework\TestCase;

final class GroupAdminTest extends TestCase
{
    public function testInstance(): void
    {
        $admin = new GroupAdmin(
            $this->createMock(GroupManager::class)
        );

        self::assertNotEmpty($admin);
    }
}
