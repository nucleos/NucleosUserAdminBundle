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
    private Packages $packages;
    private string $defaultAvatar;

    public function __construct(Packages $packages, string $defaultAvatar)
    {
        $this->packages = $packages;
        $this->defaultAvatar = $defaultAvatar;
    }

    public function avatarUrl(?UserInterface $user): string
    {
        return $this->packages->getUrl($this->defaultAvatar);
    }
}
