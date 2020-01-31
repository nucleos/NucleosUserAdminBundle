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

use Symfony\Component\Security\Core\User\UserInterface;

interface AvatarResolver
{
    public function avatarUrl(?UserInterface $user): string;
}
