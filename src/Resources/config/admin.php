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
                service('security.token_storage'),
                service('security.authorization_checker'),
                service('sonata.admin.pool'),
                service('sonata.admin.configuration'),
                '%security.role_hierarchy.roles%',
            ])
            ->call('setTranslator', [
                service('translator'),
            ])

        ->set('nucleos_user_admin.form.type.security_roles', SecurityRolesType::class)
            ->tag('form.type')
            ->args([
                service('nucleos_user_admin.editable_role_builder'),
            ])

        ->set('nucleos_user_admin.matrix_roles_builder', MatrixRolesBuilder::class)
            ->args([
                service('security.token_storage'),
                service('nucleos_user_admin.admin_roles_builder'),
                service('nucleos_user_admin.security_roles_builder'),
            ])

        ->set('nucleos_user_admin.admin_roles_builder', AdminRolesBuilder::class)
            ->args([
                service('security.authorization_checker'),
                service('sonata.admin.pool'),
                service('sonata.admin.configuration'),
                service('translator'),
            ])

        ->set('nucleos_user_admin.security_roles_builder', SecurityRolesBuilder::class)
            ->args([
                service('security.authorization_checker'),
                service('sonata.admin.configuration'),
                service('translator'),
                '%security.role_hierarchy.roles%',
            ])

        ->set(RolesMatrixType::class)
            ->public()
            ->tag('form.type')
            ->args([
                service('nucleos_user_admin.matrix_roles_builder'),
            ])

        ->set(RolesMatrixExtension::class)
            ->tag('twig.extension')

        ->set(RolesMatrixRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('nucleos_user_admin.matrix_roles_builder'),
            ])

        ->set('nucleos_user_admin.controller.user', UserCRUDController::class)
            ->public()
            ->args([
                service('event_dispatcher'),
            ])
            ->call('setContainer', [new ReferenceConfigurator(ContainerInterface::class)])

    ;
};
