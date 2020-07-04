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

use Nucleos\UserAdminBundle\Action\CheckEmailAction;
use Nucleos\UserAdminBundle\Action\CheckLoginAction;
use Nucleos\UserAdminBundle\Action\LoginAction;
use Nucleos\UserAdminBundle\Action\LogoutAction;
use Nucleos\UserAdminBundle\Action\RequestAction;
use Nucleos\UserAdminBundle\Action\ResetAction;
use Nucleos\UserAdminBundle\Action\SendEmailAction;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(RequestAction::class)
            ->public()
            ->args([
                new Reference('twig'),
                new Reference('router'),
                new Reference('security.authorization_checker'),
                new Reference('sonata.admin.pool'),
                new Reference('sonata.admin.global_template_registry'),
            ])

        ->set(SendEmailAction::class)
            ->public()
            ->args([
                new Reference('twig'),
                new Reference('router'),
                new Reference('sonata.admin.pool'),
                new Reference('sonata.admin.global_template_registry'),
                new Reference('nucleos_user.user_manager'),
                new Reference('nucleos_user.mailer'),
                new Reference('nucleos_user.util.token_generator'),
                new Parameter('nucleos_user.resetting.retry_ttl'),
            ])

        ->set(CheckEmailAction::class)
            ->public()
            ->args([
                new Reference('twig'),
                new Reference('router'),
                new Reference('sonata.admin.pool'),
                new Reference('sonata.admin.global_template_registry'),
                new Parameter('nucleos_user.resetting.retry_ttl'),
            ])

        ->set(ResetAction::class)
            ->public()
            ->args([
                new Reference('twig'),
                new Reference('router'),
                new Reference('security.authorization_checker'),
                new Reference('sonata.admin.pool'),
                new Reference('sonata.admin.global_template_registry'),
                new Reference('form.factory'),
                new Reference('nucleos_user.user_manager'),
                new Reference('nucleos_user.security.login_manager'),
                new Reference('translator'),
                new Reference('session'),
                new Parameter('nucleos_user.resetting.retry_ttl'),
                new Parameter('nucleos_user.firewall_name'),
            ])
            ->call('setLogger', [
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ])

        ->set(LoginAction::class)
            ->public()
            ->args([
                new Reference('twig'),
                new Reference('event_dispatcher'),
                new Reference('router'),
                new Reference('security.authorization_checker'),
                new Reference('sonata.admin.pool'),
                new Reference('sonata.admin.global_template_registry'),
                new Reference('security.token_storage'),
                new Reference('session'),
            ])
            ->call('setCsrfTokenManager', [
                new Reference('security.csrf.token_manager', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ])

        ->set(CheckLoginAction::class)
            ->public()

        ->set(LogoutAction::class)
            ->public()

    ;
};
