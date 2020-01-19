<?php

declare(strict_types=1);

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RolesMatrixCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('sonata.admin') as $name => $items) {
            foreach ($items as $item) {
                if (($item['show_in_roles_matrix'] ?? true) === false) {
                    $container->getDefinition('nucleos_user_admin.admin_roles_builder')
                        ->addMethodCall('addExcludeAdmin', [$name])
                    ;
                }
            }
        }
    }
}
