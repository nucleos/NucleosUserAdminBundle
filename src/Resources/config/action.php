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
                service('twig'),
                service('router'),
                service('security.authorization_checker'),
                service('sonata.admin.pool'),
                service('sonata.admin.global_template_registry'),
                service('form.factory'),
            ])

        ->set(SendEmailAction::class)
            ->public()
            ->args([
                service('router'),
                service('nucleos_user.user_manager'),
                service('nucleos_user.mailer'),
                service('nucleos_user.util.token_generator'),
                service('security.user_providers'),
                '%nucleos_user.resetting.retry_ttl%',
            ])

        ->set(CheckEmailAction::class)
            ->public()
            ->args([
                service('twig'),
                service('router'),
                service('sonata.admin.pool'),
                service('sonata.admin.global_template_registry'),
                '%nucleos_user.resetting.retry_ttl%',
            ])

        ->set(ResetAction::class)
            ->public()
            ->args([
                service('twig'),
                service('router'),
                service('security.authorization_checker'),
                service('sonata.admin.pool'),
                service('sonata.admin.global_template_registry'),
                service('form.factory'),
                service('nucleos_user.user_manager'),
                service('nucleos_user.security.login_manager'),
                service('translator'),
                service('session')->nullOnInvalid(),
                '%nucleos_user.resetting.retry_ttl%',
                '%nucleos_user.firewall_name%',
            ])
            ->call('setLogger', [
                service('logger')->ignoreOnInvalid(),
            ])

        ->set(LoginAction::class)
            ->public()
            ->args([
                service('twig'),
                service('event_dispatcher'),
                service('router'),
                service('security.authorization_checker'),
                service('sonata.admin.pool'),
                service('sonata.admin.global_template_registry'),
                service('security.token_storage'),
                service('form.factory'),
                service('security.authentication_utils'),
                service('translator'),
            ])
            ->call('setCsrfTokenManager', [
                service('security.csrf.token_manager')->ignoreOnInvalid(),
            ])

        ->set(CheckLoginAction::class)
            ->public()

        ->set(LogoutAction::class)
            ->public()
    ;
};
