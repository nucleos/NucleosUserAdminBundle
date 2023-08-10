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

namespace Nucleos\UserAdminBundle\Tests\App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nucleos\UserBundle\Model\GroupInterface;
use Nucleos\UserBundle\Model\User as BaseUser;

/**
 * @phpstan-extends BaseUser<GroupInterface>
 */
#[ORM\Entity]
#[ORM\Table(name: 'user__user')]
class User extends BaseUser
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToMany(targetEntity: Group::class)]
    #[ORM\JoinTable(name: 'user__user_group')]
    protected Collection $groups;

    public function __construct()
    {
        parent::__construct();

        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    public function toString(): string
    {
        return $this->getUsername();
    }
}
