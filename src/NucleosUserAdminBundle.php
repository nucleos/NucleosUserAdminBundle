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

namespace Nucleos\UserAdminBundle;

use Nucleos\UserAdminBundle\DependencyInjection\Compiler\GlobalVariablesCompilerPass;
use Nucleos\UserAdminBundle\DependencyInjection\Compiler\RolesMatrixCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NucleosUserAdminBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new GlobalVariablesCompilerPass());
        $container->addCompilerPass(new RolesMatrixCompilerPass());
    }
}
