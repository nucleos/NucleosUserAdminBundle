<?php

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Nucleos\UserAdminBundle\Form\Type\RolesMatrixType;
use Nucleos\UserAdminBundle\Form\Type\SecurityRolesType;
use Nucleos\UserAdminBundle\Security\EditableRolesBuilder;
use Nucleos\UserAdminBundle\Security\RolesBuilder\AdminRolesBuilder;
use Nucleos\UserAdminBundle\Security\RolesBuilder\MatrixRolesBuilder;
use Nucleos\UserAdminBundle\Security\RolesBuilder\SecurityRolesBuilder;
use Nucleos\UserAdminBundle\Twig\RolesMatrixExtension;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('nucleos_user_admin.editable_role_builder', EditableRolesBuilder::class)
            ->args([
                new Reference('security.token_storage'),
                new Reference('security.authorization_checker'),
                new Reference('sonata.admin.pool'),
                new Parameter('security.role_hierarchy.roles'),
            ])
            ->call('setTranslator', [
                new Reference('translator'),
            ])

        ->set('nucleos_user_admin.form.type.security_roles', SecurityRolesType::class)
            ->tag('form.type')
            ->args([
                new Reference('nucleos_user_admin.editable_role_builder'),
            ])

        ->set('nucleos_user_admin.matrix_roles_builder', MatrixRolesBuilder::class)
            ->args([
                new Reference('security.token_storage'),
                new Reference('nucleos_user_admin.admin_roles_builder'),
                new Reference('nucleos_user_admin.security_roles_builder'),
            ])

        ->set('nucleos_user_admin.admin_roles_builder', AdminRolesBuilder::class)
            ->args([
                new Reference('security.authorization_checker'),
                new Reference('sonata.admin.pool'),
                new Reference('translator'),
            ])

        ->set('nucleos_user_admin.security_roles_builder', SecurityRolesBuilder::class)
            ->args([
                new Reference('security.authorization_checker'),
                new Reference('sonata.admin.pool'),
                new Reference('translator'),
                new Parameter('security.role_hierarchy.roles'),
            ])

        ->set(RolesMatrixType::class)
            ->public()
            ->tag('form.type')
            ->args([
                new Reference('nucleos_user_admin.matrix_roles_builder'),
            ])

        ->set(RolesMatrixExtension::class)
            ->tag('twig.extension')
            ->args([
                new Reference('nucleos_user_admin.matrix_roles_builder'),
            ])
    ;
};
