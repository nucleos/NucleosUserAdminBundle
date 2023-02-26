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
use Doctrine\ORM\Mapping as ORM;
use Nucleos\UserBundle\Model\GroupInterface;
use Nucleos\UserBundle\Model\User as BaseUser;

/**
 * @ORM\Entity()
 *
 * @ORM\Table(name="user__user")
 *
 * @phpstan-extends BaseUser<GroupInterface>
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     *
     * @ORM\Column(type="integer")
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToMany(targetEntity="Nucleos\UserAdminBundle\Tests\App\Entity\Group")
     *
     * @ORM\JoinTable(name="user__user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected Collection $groups;

    public function __construct()
    {
        parent::__construct();

        $this->groups = new ArrayCollection();
    }

    /**
     * @return array<mixed>
     */
    public function __serialize(): array
    {
        return [
            $this->password,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->emailCanonical,
        ];
    }

    /**
     * @param mixed $serialized
     */
    public function __unserialize($serialized): void
    {
        $data = unserialize($serialized);

        [
            $this->password,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->emailCanonical
        ] = $data;
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
