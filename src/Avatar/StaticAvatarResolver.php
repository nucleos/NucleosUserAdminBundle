<?php

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserAdminBundle\Avatar;

use Symfony\Component\Asset\Packages;
use Symfony\Component\Security\Core\User\UserInterface;

final class StaticAvatarResolver implements AvatarResolver
{
    private string $defaultAvatar;
    private ?Packages $packages;

    public function __construct(string $defaultAvatar, ?Packages $packages = null)
    {
        $this->packages      = $packages;
        $this->defaultAvatar = $defaultAvatar;
    }

    public function avatarUrl(?UserInterface $user): string
    {
        if (null === $this->packages) {
            return $this->defaultAvatar;
        }

        return $this->packages->getUrl($this->defaultAvatar);
    }
}
