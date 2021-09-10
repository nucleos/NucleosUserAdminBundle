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

namespace Nucleos\UserAdminBundle\Tests\Twig;

use Nucleos\UserAdminBundle\Security\RolesBuilder\MatrixRolesBuilderInterface;
use Nucleos\UserAdminBundle\Twig\RolesMatrixRuntime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;
use Twig\Environment;

final class RolesMatrixRuntimeTest extends TestCase
{
    /**
     * @var MatrixRolesBuilderInterface&MockObject
     */
    private $rolesBuilder;

    /**
     * @var MockObject&Environment
     */
    private $environment;

    /**
     * @var MockObject&FormView
     */
    private $formView;

    protected function setUp(): void
    {
        $this->rolesBuilder = $this->createMock(MatrixRolesBuilderInterface::class);
        $this->environment  = $this->createMock(Environment::class);
        $this->formView     = $this->createMock(FormView::class);
    }

    public function testRenderRolesListWithAdminLabel(): void
    {
        $roles = [
            'SUPER_TEST_ROLE' => [
                'role'            => 'SUPER_TEST_ROLE',
                'role_translated' => 'SUPER TEST ROLE TRANSLATED',
                'is_granted'      => true,
                'admin_label'     => 'admin_name',
            ],
        ];
        $this->rolesBuilder
            ->expects(static::once())
            ->method('getRoles')
            ->willReturn($roles)
        ;

        $this->formView
            ->expects(static::never())
            ->method('getIterator')
        ;

        $this->environment
            ->expects(static::once())
            ->method('render')
            ->with('@NucleosUserAdmin/Form/roles_matrix_list.html.twig', ['roles' => []])
            ->willReturn('')
        ;

        $rolesMatrixRuntime = new RolesMatrixRuntime($this->rolesBuilder);
        $rolesMatrixRuntime->renderRolesList($this->environment, $this->formView);
    }

    public function testRenderRolesList(): void
    {
        $roles = [
            'SUPER_TEST_ROLE' => [
                'role'            => 'SUPER_TEST_ROLE',
                'role_translated' => 'SUPER TEST ROLE TRANSLATED',
                'is_granted'      => true,
            ],
        ];
        $this->rolesBuilder
            ->expects(static::once())
            ->method('getRoles')
            ->willReturn($roles)
        ;

        $form                = new FormView();
        $form->vars['value'] = 'SUPER_TEST_ROLE';

        $this->formView
            ->method('getIterator')
            ->willReturn([$form])
        ;

        $this->environment
            ->expects(static::once())
            ->method('render')
            ->with('@NucleosUserAdmin/Form/roles_matrix_list.html.twig', [
                'roles' => [
                    'SUPER_TEST_ROLE' => [
                        'role'            => 'SUPER_TEST_ROLE',
                        'role_translated' => 'SUPER TEST ROLE TRANSLATED',
                        'is_granted'      => true,
                        'form'            => $form,
                    ],
                ],
            ])
            ->willReturn('')
        ;

        $rolesMatrixRuntime = new RolesMatrixRuntime($this->rolesBuilder);
        $rolesMatrixRuntime->renderRolesList($this->environment, $this->formView);
    }

    public function testRenderRolesListWithoutFormValue(): void
    {
        $roles = [
            'SUPER_TEST_ROLE' => [
                'role'            => 'SUPER_TEST_ROLE',
                'role_translated' => 'SUPER TEST ROLE TRANSLATED',
                'is_granted'      => true,
            ],
        ];
        $this->rolesBuilder
            ->expects(static::once())
            ->method('getRoles')
            ->willReturn($roles)
        ;

        $form                = new FormView();
        $form->vars['value'] = 'WRONG_VALUE';

        $this->formView
            ->method('getIterator')
            ->willReturn([$form])
        ;

        $this->environment
            ->expects(static::once())
            ->method('render')
            ->with(
                '@NucleosUserAdmin/Form/roles_matrix_list.html.twig',
                [
                    'roles' => [
                        'SUPER_TEST_ROLE' => [
                            'role'            => 'SUPER_TEST_ROLE',
                            'role_translated' => 'SUPER TEST ROLE TRANSLATED',
                            'is_granted'      => true,
                        ],
                    ],
                ]
            )
            ->willReturn('')
        ;

        $rolesMatrixRuntime = new RolesMatrixRuntime($this->rolesBuilder);
        $rolesMatrixRuntime->renderRolesList($this->environment, $this->formView);
    }

    public function testRenderMatrixWithoutAdminLabels(): void
    {
        $roles = [
            'BASE_ROLE_FOO_%s' => [
                'role'            => 'BASE_ROLE_FOO_EDIT',
                'label'           => 'EDIT',
                'role_translated' => 'ROLE FOO TRANSLATED',
                'is_granted'      => true,
            ],
        ];
        $this->rolesBuilder
            ->expects(static::once())
            ->method('getRoles')
            ->willReturn($roles)
        ;

        $this->rolesBuilder
            ->expects(static::once())
            ->method('getPermissionLabels')
            ->willReturn(['EDIT', 'CREATE'])
        ;

        $this->formView
            ->expects(static::never())
            ->method('getIterator')
        ;

        $this->environment
            ->expects(static::once())
            ->method('render')
            ->with(
                '@NucleosUserAdmin/Form/roles_matrix.html.twig',
                [
                    'grouped_roles'     => [],
                    'permission_labels' => ['EDIT', 'CREATE'],
                ]
            )
            ->willReturn('')
        ;

        $rolesMatrixRuntime = new RolesMatrixRuntime($this->rolesBuilder);
        $rolesMatrixRuntime->renderMatrix($this->environment, $this->formView);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRenderMatrix(): void
    {
        $roles = [
            'BASE_ROLE_FOO_EDIT' => [
                'role'            => 'BASE_ROLE_FOO_EDIT',
                'label'           => 'EDIT',
                'role_translated' => 'ROLE FOO TRANSLATED',
                'admin_label'     => 'fooadmin',
                'is_granted'      => true,
            ],
        ];
        $this->rolesBuilder
            ->expects(static::once())
            ->method('getRoles')
            ->willReturn($roles)
        ;

        $this->rolesBuilder
            ->expects(static::once())
            ->method('getPermissionLabels')
            ->willReturn(['EDIT', 'CREATE'])
        ;

        $form                = new FormView();
        $form->vars['value'] = 'BASE_ROLE_FOO_EDIT';

        $this->formView
            ->expects(static::once())
            ->method('getIterator')
            ->willReturn([$form])
        ;

        $this->environment
            ->expects(static::once())
            ->method('render')
            ->with('@NucleosUserAdmin/Form/roles_matrix.html.twig', [
                'grouped_roles'     => [
                    'fooadmin' => [
                        'BASE_ROLE_FOO_EDIT' => [
                            'role'            => 'BASE_ROLE_FOO_EDIT',
                            'label'           => 'EDIT',
                            'role_translated' => 'ROLE FOO TRANSLATED',
                            'admin_label'     => 'fooadmin',
                            'is_granted'      => true,
                            'form'            => $form,
                        ],
                    ],
                ],
                'permission_labels' => ['EDIT', 'CREATE'],
            ])
            ->willReturn('')
        ;

        $rolesMatrixRuntime = new RolesMatrixRuntime($this->rolesBuilder);
        $rolesMatrixRuntime->renderMatrix($this->environment, $this->formView);
    }

    public function testRenderMatrixFormVarsNotSet(): void
    {
        $roles = [
            'BASE_ROLE_FOO_%s' => [
                'role'            => 'BASE_ROLE_FOO_EDIT',
                'label'           => 'EDIT',
                'role_translated' => 'ROLE FOO TRANSLATED',
                'admin_label'     => 'fooadmin',
                'is_granted'      => true,
            ],
        ];
        $this->rolesBuilder
            ->expects(static::once())
            ->method('getRoles')
            ->willReturn($roles)
        ;

        $this->rolesBuilder
            ->expects(static::once())
            ->method('getPermissionLabels')
            ->willReturn(['EDIT', 'CREATE'])
        ;

        $form                = new FormView();
        $form->vars['value'] = 'WRONG_VALUE';

        $this->formView
            ->expects(static::once())
            ->method('getIterator')
            ->willReturn([$form])
        ;

        $this->environment
            ->expects(static::once())
            ->method('render')
            ->with('@NucleosUserAdmin/Form/roles_matrix.html.twig', [
                'grouped_roles'     => [
                    'fooadmin' => [
                        'BASE_ROLE_FOO_%s' => [
                            'role'            => 'BASE_ROLE_FOO_EDIT',
                            'label'           => 'EDIT',
                            'role_translated' => 'ROLE FOO TRANSLATED',
                            'admin_label'     => 'fooadmin',
                            'is_granted'      => true,
                        ],
                    ],
                ],
                'permission_labels' => ['EDIT', 'CREATE'],
            ])
            ->willReturn('')
        ;

        $rolesMatrixRuntime = new RolesMatrixRuntime($this->rolesBuilder);
        $rolesMatrixRuntime->renderMatrix($this->environment, $this->formView);
    }
}
