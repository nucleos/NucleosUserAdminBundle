<?php

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader\Configurator;

use Nucleos\UserAdminBundle\Action\CheckLoginAction;
use Nucleos\UserAdminBundle\Action\LoginAction;
use Nucleos\UserAdminBundle\Action\LogoutAction;

return static function (RoutingConfigurator $routes): void {
    $routes->add('nucleos_user_admin_security_login', '/login')
        ->controller(LoginAction::class)
    ;

    $routes->add('nucleos_user_admin_security_check', '/login_check')
        ->controller(CheckLoginAction::class)
        ->methods(['POST'])
    ;

    $routes->add('nucleos_user_admin_security_logout', '/logout')
        ->controller(LogoutAction::class)
    ;
};
