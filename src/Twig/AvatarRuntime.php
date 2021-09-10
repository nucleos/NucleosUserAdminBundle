<?php

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserAdminBundle\Twig;

use Nucleos\UserAdminBundle\Avatar\AvatarResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class AvatarRuntime implements RuntimeExtensionInterface
{
    private AvatarResolver $avatarResolver;

    public function __construct(AvatarResolver $avatarResolver)
    {
        $this->avatarResolver = $avatarResolver;
    }

    public function userAvatar(?UserInterface $user): string
    {
        return $this->avatarResolver->avatarUrl($user);
    }
}
