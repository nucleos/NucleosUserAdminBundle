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

use Nucleos\UserAdminBundle\Security\Authorization\Voter\UserAclVoter;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(UserAclVoter::class)
            ->tag('monolog.logger', [
                'channel' => 'security',
            ])
            ->tag('security.voter', [
                'priority' => 255,
            ])
            ->args([
                service('security.acl.provider'),
                service('security.acl.object_identity_retrieval_strategy'),
                service('security.acl.security_identity_retrieval_strategy'),
                service('security.acl.permission.map'),
                service('logger')->nullOnInvalid(),
            ])

    ;
};
