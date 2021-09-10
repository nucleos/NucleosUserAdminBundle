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

use Nucleos\UserAdminBundle\Action\CheckEmailAction;
use Nucleos\UserAdminBundle\Action\RequestAction;
use Nucleos\UserAdminBundle\Action\ResetAction;
use Nucleos\UserAdminBundle\Action\SendEmailAction;

return static function (RoutingConfigurator $routes): void {
    $routes->add('nucleos_user_admin_resetting_request', '/request')
        ->controller(RequestAction::class)
        ->methods(['GET'])
    ;

    $routes->add('nucleos_user_admin_resetting_send_email', '/send-email')
        ->controller(SendEmailAction::class)
        ->methods(['POST'])
    ;

    $routes->add('nucleos_user_admin_resetting_check_email', '/check-email')
        ->controller(CheckEmailAction::class)
        ->methods(['GET'])
    ;

    $routes->add('nucleos_user_admin_resetting_reset', '/reset/{token}')
        ->controller(ResetAction::class)
        ->methods(['GET', 'POST'])
    ;
};
