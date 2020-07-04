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

use Nucleos\UserAdminBundle\Avatar\AvatarResolver;
use Nucleos\UserAdminBundle\Avatar\StaticAvatarResolver;
use Nucleos\UserAdminBundle\Twig\AvatarExtension;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(AvatarExtension::class)
            ->tag('twig.extension')
            ->args([
                new Reference(AvatarResolver::class),
                null,
                [],
            ])

        ->set(StaticAvatarResolver::class)
            ->args([
                new Parameter('nucleos_user_admin.default_avata'),
            ])

        ->alias(AvatarResolver::class, StaticAvatarResolver::class)

    ;
};
