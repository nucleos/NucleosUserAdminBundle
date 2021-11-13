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

use Nucleos\UserAdminBundle\Controller\UserCRUDController;
use Nucleos\UserAdminBundle\Form\Type\RolesMatrixType;
use Nucleos\UserAdminBundle\Form\Type\SecurityRolesType;
use Nucleos\UserAdminBundle\Security\EditableRolesBuilder;
use Nucleos\UserAdminBundle\Security\RolesBuilder\AdminRolesBuilder;
use Nucleos\UserAdminBundle\Security\RolesBuilder\MatrixRolesBuilder;
use Nucleos\UserAdminBundle\Security\RolesBuilder\SecurityRolesBuilder;
use Nucleos\UserAdminBundle\Twig\RolesMatrixExtension;
use Nucleos\UserAdminBundle\Twig\RolesMatrixRuntime;
use Psr\Container\ContainerInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set('nucleos_user_admin.editable_role_builder', EditableRolesBuilder::class)
            ->args([
                ref('security.token_storage'),
                ref('security.authorization_checker'),
                ref('sonata.admin.pool'),
                ref('sonata.admin.configuration'),
                '%security.role_hierarchy.roles%',
            ])
            ->call('setTranslator', [
                ref('translator'),
            ])

        ->set('nucleos_user_admin.form.type.security_roles', SecurityRolesType::class)
            ->tag('form.type')
            ->args([
                ref('nucleos_user_admin.editable_role_builder'),
            ])

        ->set('nucleos_user_admin.matrix_roles_builder', MatrixRolesBuilder::class)
            ->args([
                ref('security.token_storage'),
                ref('nucleos_user_admin.admin_roles_builder'),
                ref('nucleos_user_admin.security_roles_builder'),
            ])

        ->set('nucleos_user_admin.admin_roles_builder', AdminRolesBuilder::class)
            ->args([
                ref('security.authorization_checker'),
                ref('sonata.admin.pool'),
                ref('sonata.admin.configuration'),
                ref('translator'),
            ])

        ->set('nucleos_user_admin.security_roles_builder', SecurityRolesBuilder::class)
            ->args([
                ref('security.authorization_checker'),
                ref('sonata.admin.configuration'),
                ref('translator'),
                '%security.role_hierarchy.roles%',
            ])

        ->set(RolesMatrixType::class)
            ->public()
            ->tag('form.type')
            ->args([
                ref('nucleos_user_admin.matrix_roles_builder'),
            ])

        ->set(RolesMatrixExtension::class)
            ->tag('twig.extension')

        ->set(RolesMatrixRuntime::class)
            ->tag('twig.runtime')
            ->args([
                ref('nucleos_user_admin.matrix_roles_builder'),
            ])

        ->set('nucleos_user_admin.controller.user', UserCRUDController::class)
            ->public()
            ->args([
                ref('event_dispatcher'),
            ])
            ->call('setContainer', [new ReferenceConfigurator(ContainerInterface::class)])

    ;
};
