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

namespace Nucleos\UserAdminBundle\Tests\DependencyInjection\Compiler;

use Nucleos\UserAdminBundle\DependencyInjection\Compiler\RolesMatrixCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class RolesMatrixCompilerPassTest extends TestCase
{
    public function testProcess(): void
    {
        $definition = $this->createMock(Definition::class);

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects(static::once())
            ->method('getDefinition')
            ->with('nucleos_user_admin.admin_roles_builder')
            ->willReturn($definition)
        ;

        $taggedServices = [
            'sonata.admin.foo'  => [0 => ['show_in_roles_matrix' => true]],
            'sonata.admin.bar'  => [0 => ['show_in_roles_matrix' => false]],
            'sonata.admin.test' => [],
        ];

        $container
            ->expects(static::once())
            ->method('findTaggedServiceIds')
            ->with('sonata.admin')
            ->willReturn($taggedServices)
        ;

        $definition
            ->expects(static::once())
            ->method('addMethodCall')
            ->with('addExcludeAdmin', ['sonata.admin.bar'])
        ;

        $compilerPass = new RolesMatrixCompilerPass();
        $compilerPass->process($container);
    }
}
