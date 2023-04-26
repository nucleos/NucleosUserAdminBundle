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

namespace Nucleos\UserAdminBundle\Tests\Security\RolesBuilder;

use Nucleos\UserAdminBundle\Security\RolesBuilder\AdminRolesBuilderInterface;
use Nucleos\UserAdminBundle\Security\RolesBuilder\ExpandableRolesBuilderInterface;
use Nucleos\UserAdminBundle\Security\RolesBuilder\MatrixRolesBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class MatrixRolesBuilderTest extends TestCase
{
    /**
     * @var MockObject&TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var MockObject&TokenInterface
     */
    private $token;

    /**
     * @var AdminRolesBuilderInterface&MockObject
     */
    private $adminRolesBuilder;

    /**
     * @var ExpandableRolesBuilderInterface&MockObject
     */
    private $securityRolesBuilder;

    protected function setUp(): void
    {
        $this->tokenStorage         = $this->createMock(TokenStorageInterface::class);
        $this->token                = $this->createMock(TokenInterface::class);
        $this->adminRolesBuilder    = $this->createMock(AdminRolesBuilderInterface::class);
        $this->securityRolesBuilder = $this->createMock(ExpandableRolesBuilderInterface::class);
    }

    public function testGetPermissionLabels(): void
    {
        $expected = ['EDIT' => 'EDIT', 'LIST' => 'LIST', 'CREATE' => 'CREATE'];

        $this->adminRolesBuilder->method('getPermissionLabels')
            ->willReturn($expected)
        ;

        $matrixRolesBuilder = new MatrixRolesBuilder(
            $this->tokenStorage,
            $this->adminRolesBuilder,
            $this->securityRolesBuilder
        );

        self::assertSame($expected, $matrixRolesBuilder->getPermissionLabels());
    }

    public function testGetRoles(): void
    {
        $this->tokenStorage->method('getToken')
            ->willReturn($this->token)
        ;

        $adminRoles = [
            'ROLE_SONATA_FOO_GUEST' => [
                'role'            => 'ROLE_SONATA_FOO_GUEST',
                'label'           => 'GUEST',
                'role_translated' => 'ROLE_SONATA_FOO_GUEST',
                'is_granted'      => false,
                'admin_label'     => 'Foo',
            ],
        ];

        $this->adminRolesBuilder->method('getRoles')
            ->willReturn($adminRoles)
        ;

        $securityRoles = [
            'ROLE_FOO' => [
                'role'            => 'ROLE_FOO',
                'role_translated' => 'ROLE_FOO: ROLE_BAR, ROLE_ADMIN',
                'is_granted'      => true,
            ],
        ];

        $this->securityRolesBuilder->method('getRoles')
            ->willReturn($securityRoles)
        ;

        $matrixRolesBuilder = new MatrixRolesBuilder(
            $this->tokenStorage,
            $this->adminRolesBuilder,
            $this->securityRolesBuilder
        );

        $expected = array_merge($securityRoles, $adminRoles);

        self::assertSame($expected, $matrixRolesBuilder->getRoles());
    }

    public function testGetRolesNoToken(): void
    {
        $this->tokenStorage->method('getToken')
            ->willReturn(null)
        ;

        $matrixRolesBuilder = new MatrixRolesBuilder(
            $this->tokenStorage,
            $this->adminRolesBuilder,
            $this->securityRolesBuilder
        );

        self::assertEmpty($matrixRolesBuilder->getRoles());
    }
}
