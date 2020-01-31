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
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AvatarExtension extends AbstractExtension
{
    /**
     * @var AvatarResolver
     */
    private $avatarResolver;

    public function __construct(AvatarResolver $avatarResolver)
    {
        $this->avatarResolver = $avatarResolver;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('userAvatar', [$this, 'userAvatar']),
        ];
    }

    public function userAvatar(?UserInterface $user): string
    {
        return $this->avatarResolver->avatarUrl($user);
    }
}
