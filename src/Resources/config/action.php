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

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(RequestAction::class)
            ->public()
            ->args([
                ref('twig'),
                ref('router'),
                ref('security.authorization_checker'),
                ref('sonata.admin.pool'),
                ref('sonata.admin.global_template_registry'),
                ref('form.factory'),
            ])

        ->set(SendEmailAction::class)
            ->public()
            ->args([
                ref('router'),
                ref('nucleos_user.user_manager'),
                ref('nucleos_user.mailer'),
                ref('nucleos_user.util.token_generator'),
                '%nucleos_user.resetting.retry_ttl%',
            ])

        ->set(CheckEmailAction::class)
            ->public()
            ->args([
                ref('twig'),
                ref('router'),
                ref('sonata.admin.pool'),
                ref('sonata.admin.global_template_registry'),
                '%nucleos_user.resetting.retry_ttl%',
            ])

        ->set(ResetAction::class)
            ->public()
            ->args([
                ref('twig'),
                ref('router'),
                ref('security.authorization_checker'),
                ref('sonata.admin.pool'),
                ref('sonata.admin.global_template_registry'),
                ref('form.factory'),
                ref('nucleos_user.user_manager'),
                ref('nucleos_user.security.login_manager'),
                ref('translator'),
                ref('session'),
                '%nucleos_user.resetting.retry_ttl%',
                '%nucleos_user.firewall_name%',
            ])
            ->call('setLogger', [
                ref('logger')->ignoreOnInvalid(),
            ])

        ->set(LoginAction::class)
            ->public()
            ->args([
                ref('twig'),
                ref('event_dispatcher'),
                ref('router'),
                ref('security.authorization_checker'),
                ref('sonata.admin.pool'),
                ref('sonata.admin.global_template_registry'),
                ref('security.token_storage'),
                ref('form.factory'),
                ref('security.authentication_utils'),
                ref('translator'),
            ])
            ->call('setCsrfTokenManager', [
                ref('security.csrf.token_manager')->ignoreOnInvalid(),
            ])

        ->set(CheckLoginAction::class)
            ->public()

        ->set(LogoutAction::class)
            ->public()

    ;
};
