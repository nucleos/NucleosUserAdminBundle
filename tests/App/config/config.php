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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Nucleos\UserAdminBundle\Tests\App\Entity\Group;
use Nucleos\UserAdminBundle\Tests\App\Entity\User;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', ['secret' => 'MySecret']);

    $containerConfigurator->extension('framework', ['assets' => null]);

    $containerConfigurator->extension('framework', ['test' => true]);

    $containerConfigurator->extension('framework', ['session' => ['storage_factory_id' => 'session.storage.factory.mock_file']]);

    $containerConfigurator->extension('framework', ['cache' => ['pools' => ['avatar.preview.cache' => ['adapter' => 'cache.app', 'default_lifetime' => 60]]]]);

    $containerConfigurator->extension('twig', ['strict_variables' => true]);

    $containerConfigurator->extension('twig', ['exception_controller' => null]);

    $containerConfigurator->extension('security', ['encoders' => [
        \Symfony\Component\Security\Core\User\User::class => 'auto',
    ]]);

    $containerConfigurator->extension('security', ['providers' => [
        'test_users' => [
            'memory' => [
                'users' => [
                    'user' => ['password' => 'password', 'roles' => ['ROLE_USER']],
                ],
            ],
        ],
    ]]);

    $containerConfigurator->extension('security', [
        'firewalls' => [
            'main' => [
                'anonymous'  => true,
                'http_basic' => ['provider' => 'test_users'],
            ],
        ],
    ]);

    $containerConfigurator->extension('security', ['access_control' => [['path' => '^/.*', 'role' => 'IS_AUTHENTICATED_ANONYMOUSLY']]]);

    $containerConfigurator->extension('doctrine', ['dbal' => ['url' => 'sqlite:///%kernel.cache_dir%/data.db']]);

    $containerConfigurator->extension('doctrine', ['orm' => [
        'auto_mapping' => true,
        'mappings'     => [
            'App' => [
                'is_bundle' => false,
                'type'      => 'annotation',
                'dir'       => '%kernel.project_dir%/Entity',
                'prefix'    => 'Nucleos\UserAdminBundle\Tests\App\Entity',
                'alias'     => 'App',
            ],
        ],
    ]]);

    $containerConfigurator->extension('nucleos_user', ['db_driver' => 'orm']);

    $containerConfigurator->extension('nucleos_user', ['firewall_name' => 'main']);

    $containerConfigurator->extension('nucleos_user', ['from_email' => 'no-reply@localhost']);

    $containerConfigurator->extension('nucleos_user', ['user_class' => User::class]);

    $containerConfigurator->extension('nucleos_user', ['group' => ['group_class' => Group::class]]);
};
