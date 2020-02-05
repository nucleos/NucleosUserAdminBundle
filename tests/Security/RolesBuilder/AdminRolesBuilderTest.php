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

use Nucleos\UserAdminBundle\Security\RolesBuilder\AdminRolesBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AdminRolesBuilderTest extends TestCase
{
    /**
     * @var MockObject&SecurityHandlerInterface
     */
    private $securityHandler;

    /**
     * @var MockObject&AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var MockObject&AdminInterface
     */
    private $admin;

    /**
     * @var MockObject&TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var MockObject&TokenInterface
     */
    private $token;

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
    private $securityInformation = [
        'GUEST'  => [0 => 'VIEW', 1 => 'LIST'],
        'STAFF'  => [0 => 'EDIT', 1 => 'LIST', 2 => 'CREATE'],
        'EDITOR' => [0 => 'OPERATOR', 1 => 'EXPORT'],
        'ADMIN'  => [0 => 'MASTER'],
    ];

    protected function setUp(): void
    {
        $this->securityHandler      = $this->createMock(SecurityHandlerInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->admin                = $this->createMock(AdminInterface::class);
        $this->tokenStorage         = $this->createMock(TokenStorageInterface::class);
        $this->token                = $this->createMock(TokenInterface::class);
        $this->pool                 = $this->createMock(Pool::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
    }

    public function testGetPermissionLabels(): void
    {
        $this->translator->method('trans')
            ->willReturn(static::returnArgument(0))
        ;

        $this->securityHandler->method('getBaseRole')
            ->willReturn('ROLE_SONATA_FOO_%s')
        ;

        $this->admin->method('getSecurityHandler')
            ->willReturn($this->securityHandler)
        ;

        $this->admin->method('getLabel')
            ->willReturn('custom')
        ;

        $this->admin->method('getTranslator')
            ->willReturn($this->translator)
        ;

        $this->admin->method('getSecurityInformation')
            ->willReturn($this->securityInformation)
        ;

        $this->admin->method('getBaseCodeRoute')
            ->willReturn('sonata.admin.bar')
        ;

        $this->pool->expects(static::once())
            ->method('getAdminServiceIds')
            ->willReturn(['sonata.admin.bar'])
        ;

        $this->pool->expects(static::once())
            ->method('getInstance')
            ->with('sonata.admin.bar')
            ->willReturn($this->admin)
        ;

        $this->pool->method('getAdminGroups')
            ->willReturn([])
        ;

        $rolesBuilder = new AdminRolesBuilder(
            $this->authorizationChecker,
            $this->pool,
            $this->translator
        );

        $expected = [
            'GUEST'  => 'GUEST',
            'STAFF'  => 'STAFF',
            'EDITOR' => 'EDITOR',
            'ADMIN'  => 'ADMIN',
        ];

        static::assertSame($expected, $rolesBuilder->getPermissionLabels());
    }

    public function testGetRoles(): void
    {
        $this->translator->method('trans')
            ->willReturn(static::returnArgument(0))
        ;

        $this->securityHandler->method('getBaseRole')
            ->willReturn('ROLE_SONATA_FOO_%s')
        ;

        $this->admin->method('getSecurityHandler')
            ->willReturn($this->securityHandler)
        ;

        $this->admin->method('getTranslator')
            ->willReturn($this->translator)
        ;

        $this->admin->method('getSecurityInformation')
            ->willReturn($this->securityInformation)
        ;

        $this->admin->method('getLabel')
            ->willReturn('Foo')
        ;

        $this->admin->method('getBaseCodeRoute')
            ->willReturn('sonata.admin.bar')
        ;

        $this->pool->expects(static::once())
            ->method('getAdminServiceIds')
            ->willReturn(['sonata.admin.bar'])
        ;

        $this->pool->expects(static::once())
            ->method('getInstance')
            ->with('sonata.admin.bar')
            ->willReturn($this->admin)
        ;

        $this->pool->method('getAdminGroups')
            ->willReturn([])
        ;

        $rolesBuilder = new AdminRolesBuilder(
            $this->authorizationChecker,
            $this->pool,
            $this->translator
        );

        $expected = [
            'ROLE_SONATA_FOO_GUEST'  => [
                'role'            => 'ROLE_SONATA_FOO_GUEST',
                'label'           => 'GUEST',
                'role_translated' => 'ROLE_SONATA_FOO_GUEST',
                'is_granted'      => false,
                'admin_label'     => 'sonata > Foo',
            ],
            'ROLE_SONATA_FOO_STAFF'  => [
                'role'            => 'ROLE_SONATA_FOO_STAFF',
                'label'           => 'STAFF',
                'role_translated' => 'ROLE_SONATA_FOO_STAFF',
                'is_granted'      => false,
                'admin_label'     => 'sonata > Foo',
            ],
            'ROLE_SONATA_FOO_EDITOR' => [
                'role'            => 'ROLE_SONATA_FOO_EDITOR',
                'label'           => 'EDITOR',
                'role_translated' => 'ROLE_SONATA_FOO_EDITOR',
                'is_granted'      => false,
                'admin_label'     => 'sonata > Foo',
            ],
            'ROLE_SONATA_FOO_ADMIN'  => [
                'role'            => 'ROLE_SONATA_FOO_ADMIN',
                'label'           => 'ADMIN',
                'role_translated' => 'ROLE_SONATA_FOO_ADMIN',
                'is_granted'      => false,
                'admin_label'     => 'sonata > Foo',
            ],
        ];

        static::assertSame($expected, $rolesBuilder->getRoles());
    }

    public function testGetAddExcludeAdmins(): void
    {
        $rolesBuilder = new AdminRolesBuilder(
            $this->authorizationChecker,
            $this->pool,
            $this->translator
        );
        $rolesBuilder->addExcludeAdmin('sonata.admin.bar');

        static::assertSame(['sonata.admin.bar'], $rolesBuilder->getExcludeAdmins());
    }
}
