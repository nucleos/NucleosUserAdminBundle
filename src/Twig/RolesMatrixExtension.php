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

namespace Nucleos\UserAdminBundle\Twig;

use Nucleos\UserAdminBundle\Security\RolesBuilder\MatrixRolesBuilderInterface;
use Symfony\Component\Form\FormView;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RolesMatrixExtension extends AbstractExtension
{
    private MatrixRolesBuilderInterface $rolesBuilder;

    public function __construct(MatrixRolesBuilderInterface $rolesBuilder)
    {
        $this->rolesBuilder = $rolesBuilder;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderMatrix', [$this, 'renderMatrix'], ['needs_environment' => true]),
            new TwigFunction('renderRolesList', [$this, 'renderRolesList'], ['needs_environment' => true]),
        ];
    }

    public function renderRolesList(Environment $environment, FormView $form): string
    {
        $roles = $this->rolesBuilder->getRoles();
        foreach ($roles as $role => $attributes) {
            if (isset($attributes['admin_label'])) {
                unset($roles[$role]);

                continue;
            }

            $roles[$role] = $attributes;
            foreach ($form->getIterator() as $child) {
                if ($child->vars['value'] === $role) {
                    $roles[$role]['form'] = $child;
                }
            }
        }

        return $environment->render('@NucleosUserAdmin/Form/roles_matrix_list.html.twig', [
            'roles' => $roles,
        ]);
    }

    public function renderMatrix(Environment $environment, FormView $form): string
    {
        $groupedRoles = [];
        foreach ($this->rolesBuilder->getRoles() as $role => $attributes) {
            if (!isset($attributes['admin_label'])) {
                continue;
            }

            $groupedRoles[$attributes['admin_label']][$role] = $attributes;
            foreach ($form->getIterator() as $child) {
                if ($child->vars['value'] === $role) {
                    $groupedRoles[$attributes['admin_label']][$role]['form'] = $child;
                }
            }
        }

        return $environment->render('@NucleosUserAdmin/Form/roles_matrix.html.twig', [
            'grouped_roles'     => $groupedRoles,
            'permission_labels' => $this->rolesBuilder->getPermissionLabels(),
        ]);
    }
}
