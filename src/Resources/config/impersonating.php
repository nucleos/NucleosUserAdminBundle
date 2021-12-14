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

use Nucleos\UserAdminBundle\Twig\ImpersonateExtension;
use Nucleos\UserAdminBundle\Twig\ImpersonateRuntime;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(ImpersonateExtension::class)
            ->tag('twig.extension')

        ->set(ImpersonateRuntime::class)
            ->tag('twig.runtime')
            ->args([
                service('router'),
                null,
                [],
            ])
    ;
};
