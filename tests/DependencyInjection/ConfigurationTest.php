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

namespace Nucleos\UserAdminBundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use Nucleos\UserAdminBundle\Admin\Entity\GroupAdmin;
use Nucleos\UserAdminBundle\Admin\Entity\UserAdmin;
use Nucleos\UserAdminBundle\Avatar\StaticAvatarResolver;
use Nucleos\UserAdminBundle\Controller\UserCRUDController;
use Nucleos\UserAdminBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Controller\CRUDController;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    public function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    public function testDefault(): void
    {
        $this->assertProcessedConfigurationEquals(
            [
                [
                ],
            ],
            [
                'security_acl'         => false,
                'admin'                => [
                    'user'  => [
                        'class'       => UserAdmin::class,
                        'controller'  => UserCRUDController::class,
                        'translation' => 'NucleosUserAdminBundle',
                    ],
                    'group' => [
                        'class'       => GroupAdmin::class,
                        'controller'  => CRUDController::class,
                        'translation' => 'NucleosUserAdminBundle',
                    ],
                ],
                'avatar'              => [
                    'resolver'       => StaticAvatarResolver::class,
                    'default_avatar' => 'bundles/nucleosuseradmin/default_avatar.png',
                ],
            ]
        );
    }
}
