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
use Sonata\AdminBundle\SonataConfiguration;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SecurityRolesBuilderTest extends TestCase
{
    /**
     * @var AuthorizationCheckerInterface&MockObject
     */
    private $authorizationChecker;

    /**
     * @var AdminInterface<object>&MockObject
     */
    private $admin;

    private SonataConfiguration $configuration;

    /**
     * @var MockObject&TranslatorInterface
     */
    private $translator;

    /**
     * @var string[][]
     */
    private array $rolesHierarchy = ['ROLE_FOO' => ['ROLE_BAR', 'ROLE_ADMIN']];

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->admin                = $this->createMock(AdminInterface::class);
        $this->configuration        = new SonataConfiguration('title', 'logo.png', [
            'role_super_admin' => 'ROLE_SUPER_ADMIN',
            'role_admin'       => 'ROLE_SONATA_ADMIN',
        ]);
        $this->translator           = $this->createMock(TranslatorInterface::class);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetRoles(): void
    {
        $securityRolesBuilder = new SecurityRolesBuilder(
            $this->authorizationChecker,
            $this->configuration,
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

        self::assertSame($expected, $securityRolesBuilder->getExpandedRoles());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetRolesNotExpanded(): void
    {
        $securityRolesBuilder = new SecurityRolesBuilder(
            $this->authorizationChecker,
            $this->configuration,
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

        self::assertSame($expected, $securityRolesBuilder->getRoles(null));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetRolesWithExistingRole(): void
    {
        $this->rolesHierarchy['ROLE_STAFF'] = ['ROLE_SUPER_ADMIN', 'ROLE_SUPER_ADMIN'];

        $securityRolesBuilder = new SecurityRolesBuilder(
            $this->authorizationChecker,
            $this->configuration,
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

        self::assertSame($expected, $securityRolesBuilder->getExpandedRoles());
    }
}
