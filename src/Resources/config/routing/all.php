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

return static function (RoutingConfigurator $routes): void {
    $routes->import('@NucleosUserAdminBundle/Resources/config/routing/admin_security.php')
        ->prefix('/admin')
    ;
    $routes->import('@NucleosUserAdminBundle/Resources/config/routing/admin_resetting.php')
        ->prefix('/admin/resetting')
    ;
};
