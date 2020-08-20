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

use Nucleos\UserAdminBundle\Security\RolesBuilder\SecurityRolesBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SecurityRolesBuilderTest extends TestCase
{
    /**
     * @var MockObject&AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var MockObject&AdminInterface
     */
    private $admin;

    /**
     * @var MockObject&Pool
     */
    private $pool;

    /**
     * @var MockObject&TranslatorInterface
     */
    private $translator;

    /**
     * @var string[][]
     */
    private $rolesHierarchy = ['ROLE_FOO' => ['ROLE_BAR', 'ROLE_ADMIN']];

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->admin                = $this->createMock(AdminInterface::class);
        $this->pool                 = $this->createMock(Pool::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
    }

    public function testGetRoles(): void
    {
        $this->pool->expects(static::exactly(2))
            ->method('getOption')
            ->withConsecutive(
                ['role_super_admin'],
                ['role_admin']
            )
            ->willReturn(
                'ROLE_SUPER_ADMIN',
                'ROLE_SONATA_ADMIN'
            )
        ;

        $securityRolesBuilder = new SecurityRolesBuilder(
            $this->authorizationChecker,
            $this->pool,
            $this->translator,
            $this->rolesHierarchy
        );

        $this->authorizationChecker->method('isGranted')
            ->willReturn(true)
        ;

        $expected = [
            'ROLE_SUPER_ADMIN'  => [
                'role'            => 'ROLE_SUPER_ADMIN',
                'role_translated' => 'ROLE_SUPER_ADMIN',
                'is_granted'      => true,
            ],
            'ROLE_SONATA_ADMIN' => [
                'role'            => 'ROLE_SONATA_ADMIN',
                'role_translated' => 'ROLE_SONATA_ADMIN',
                'is_granted'      => true,
            ],
            'ROLE_FOO'          => [
                'role'            => 'ROLE_FOO',
                'role_translated' => 'ROLE_FOO: ROLE_BAR, ROLE_ADMIN',
                'is_granted'      => true,
            ],
            'ROLE_BAR'          => [
                'role'            => 'ROLE_BAR',
                'role_translated' => 'ROLE_BAR',
                'is_granted'      => true,
            ],
            'ROLE_ADMIN'        => [
                'role'            => 'ROLE_ADMIN',
                'role_translated' => 'ROLE_ADMIN',
                'is_granted'      => true,
            ],
        ];

        static::assertSame($expected, $securityRolesBuilder->getExpandedRoles());
    }

    public function testGetRolesNotExpanded(): void
    {
        $this->pool->expects(static::exactly(2))
            ->method('getOption')
            ->withConsecutive(
                ['role_super_admin'],
                ['role_admin']
            )
            ->willReturn(
                'ROLE_SUPER_ADMIN',
                'ROLE_SONATA_ADMIN'
            )
        ;

        $securityRolesBuilder = new SecurityRolesBuilder(
            $this->authorizationChecker,
            $this->pool,
            $this->translator,
            $this->rolesHierarchy
        );

        $this->authorizationChecker->method('isGranted')
            ->willReturn(true)
        ;

        $expected = [
            'ROLE_SUPER_ADMIN'  => [
                'role'            => 'ROLE_SUPER_ADMIN',
                'role_translated' => 'ROLE_SUPER_ADMIN',
                'is_granted'      => true,
            ],
            'ROLE_SONATA_ADMIN' => [
                'role'            => 'ROLE_SONATA_ADMIN',
                'role_translated' => 'ROLE_SONATA_ADMIN',
                'is_granted'      => true,
            ],
            'ROLE_FOO'          => [
                'role'            => 'ROLE_FOO',
                'role_translated' => 'ROLE_FOO',
                'is_granted'      => true,
            ],
            'ROLE_BAR'          => [
                'role'            => 'ROLE_BAR',
                'role_translated' => 'ROLE_BAR',
                'is_granted'      => true,
            ],
            'ROLE_ADMIN'        => [
                'role'            => 'ROLE_ADMIN',
                'role_translated' => 'ROLE_ADMIN',
                'is_granted'      => true,
            ],
        ];

        static::assertSame($expected, $securityRolesBuilder->getRoles(null));
    }

    public function testGetRolesWithExistingRole(): void
    {
        $this->pool->expects(static::exactly(2))
            ->method('getOption')
            ->withConsecutive(
                ['role_super_admin'],
                ['role_admin']
            )
            ->willReturn(
                'ROLE_SUPER_ADMIN',
                'ROLE_SONATA_ADMIN'
            )
        ;

        $this->rolesHierarchy['ROLE_STAFF'] = ['ROLE_SUPER_ADMIN', 'ROLE_SUPER_ADMIN'];

        $securityRolesBuilder = new SecurityRolesBuilder(
            $this->authorizationChecker,
            $this->pool,
            $this->translator,
            $this->rolesHierarchy
        );

        $this->authorizationChecker->method('isGranted')
            ->willReturn(true)
        ;

        $expected = [
            'ROLE_SUPER_ADMIN'  => [
                'role'            => 'ROLE_SUPER_ADMIN',
                'role_translated' => 'ROLE_SUPER_ADMIN',
                'is_granted'      => true,
            ],
            'ROLE_SONATA_ADMIN' => [
                'role'            => 'ROLE_SONATA_ADMIN',
                'role_translated' => 'ROLE_SONATA_ADMIN',
                'is_granted'      => true,
            ],
            'ROLE_FOO'          => [
                'role'            => 'ROLE_FOO',
                'role_translated' => 'ROLE_FOO: ROLE_BAR, ROLE_ADMIN',
                'is_granted'      => true,
            ],
            'ROLE_BAR'          => [
                'role'            => 'ROLE_BAR',
                'role_translated' => 'ROLE_BAR',
                'is_granted'      => true,
            ],
            'ROLE_ADMIN'        => [
                'role'            => 'ROLE_ADMIN',
                'role_translated' => 'ROLE_ADMIN',
                'is_granted'      => true,
            ],
            'ROLE_STAFF'        => [
                'role'            => 'ROLE_STAFF',
                'role_translated' => 'ROLE_STAFF: ROLE_SUPER_ADMIN, ROLE_SUPER_ADMIN',
                'is_granted'      => true,
            ],
        ];

        static::assertSame($expected, $securityRolesBuilder->getExpandedRoles());
    }
}
