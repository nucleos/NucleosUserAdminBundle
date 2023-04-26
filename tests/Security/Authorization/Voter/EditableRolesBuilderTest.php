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

namespace Nucleos\UserAdminBundle\Tests\Security\Authorization\Voter;

use Nucleos\UserAdminBundle\Security\EditableRolesBuilder;
use Nucleos\UserAdminBundle\Tests\Fixtures\PoolMockFactory;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Sonata\AdminBundle\SonataConfiguration;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class EditableRolesBuilderTest extends TestCase
{
    public function testRolesFromHierarchy(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::any())->method('getToken')->willReturn($token);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::any())->method('isGranted')->willReturn(true);

        $pool          = PoolMockFactory::create();
        $configuration = new SonataConfiguration('title', 'logo.png', []);

        $rolesHierarchy = [
            'ROLE_ADMIN'       => [
                0 => 'ROLE_USER',
            ],
            'ROLE_SUPER_ADMIN' => [
                0 => 'ROLE_USER',
                1 => 'ROLE_SONATA_ADMIN',
                2 => 'ROLE_ADMIN',
                3 => 'ROLE_ALLOWED_TO_SWITCH',
                4 => 'ROLE_SONATA_PAGE_ADMIN_PAGE_EDIT',
                5 => 'ROLE_SONATA_PAGE_ADMIN_BLOCK_EDIT',
            ],
            'SONATA'           => [],
        ];

        $expected = [
            'ROLE_ADMIN'                        => 'ROLE_ADMIN: ROLE_USER',
            'ROLE_USER'                         => 'ROLE_USER',
            'ROLE_SUPER_ADMIN'                  => 'ROLE_SUPER_ADMIN: ROLE_USER, ROLE_SONATA_ADMIN, ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH, ROLE_SONATA_PAGE_ADMIN_PAGE_EDIT, ROLE_SONATA_PAGE_ADMIN_BLOCK_EDIT',
            'ROLE_SONATA_ADMIN'                 => 'ROLE_SONATA_ADMIN',
            'ROLE_ALLOWED_TO_SWITCH'            => 'ROLE_ALLOWED_TO_SWITCH',
            'ROLE_SONATA_PAGE_ADMIN_PAGE_EDIT'  => 'ROLE_SONATA_PAGE_ADMIN_PAGE_EDIT',
            'ROLE_SONATA_PAGE_ADMIN_BLOCK_EDIT' => 'ROLE_SONATA_PAGE_ADMIN_BLOCK_EDIT',
            'SONATA'                            => 'SONATA: ',
        ];

        $builder       = new EditableRolesBuilder($tokenStorage, $authorizationChecker, $pool, $configuration, $rolesHierarchy);
        $roles         = $builder->getRoles();
        $rolesReadOnly = $builder->getRolesReadOnly();

        self::assertEmpty($rolesReadOnly);
        self::assertSame($expected, $roles);
    }

    public function testRolesFromAdminWithMasterAdmin(): void
    {
        $securityHandler = $this->createMock(SecurityHandlerInterface::class);
        $securityHandler->expects(self::exactly(2))->method('getBaseRole')->willReturn('ROLE_FOO_%s');

        $admin = $this->createMock(AdminInterface::class);
        $admin->expects(self::exactly(2))->method('isGranted')->willReturn(true);
        $admin->expects(self::exactly(2))->method('getSecurityInformation')->willReturn(['GUEST' => [
            0 => 'VIEW',
            1 => 'LIST',
        ], 'STAFF'                                                                                => [
            0 => 'EDIT',
            1 => 'LIST',
            2 => 'CREATE',
        ], 'EDITOR'                                                                               => [
            0 => 'OPERATOR',
            1 => 'EXPORT',
        ], 'ADMIN'                                                                                => [
            0 => 'MASTER',
        ]]);
        $admin->expects(self::exactly(2))->method('getSecurityHandler')->willReturn($securityHandler);

        $token = $this->createMock(TokenInterface::class);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::any())->method('getToken')->willReturn($token);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::any())->method('isGranted')->willReturn(true);

        $pool  = PoolMockFactory::create([
            'myadmin' => $admin,
        ]);
        $configuration = new SonataConfiguration('title', 'logo.png', []);

        $builder = new EditableRolesBuilder($tokenStorage, $authorizationChecker, $pool, $configuration, []);

        $expected = [
            'ROLE_FOO_GUEST'  => 'ROLE_FOO_GUEST',
            'ROLE_FOO_STAFF'  => 'ROLE_FOO_STAFF',
            'ROLE_FOO_EDITOR' => 'ROLE_FOO_EDITOR',
            'ROLE_FOO_ADMIN'  => 'ROLE_FOO_ADMIN',
        ];

        $roles         = $builder->getRoles();
        $rolesReadOnly = $builder->getRolesReadOnly();
        self::assertEmpty($rolesReadOnly);
        self::assertSame($expected, $roles);
    }

    public function testWithNoSecurityToken(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::any())->method('getToken')->willReturn(null);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::any())->method('isGranted')->willReturn(false);

        $pool          = PoolMockFactory::create();
        $configuration = new SonataConfiguration('title', 'logo.png', []);

        $builder = new EditableRolesBuilder($tokenStorage, $authorizationChecker, $pool, $configuration, []);

        $roles         = $builder->getRoles();
        $rolesReadOnly = $builder->getRolesReadOnly();

        self::assertEmpty($roles);
        self::assertEmpty($rolesReadOnly);
    }
}
