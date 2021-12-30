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

namespace Nucleos\UserAdminBundle\Twig;

use LogicException;
use Nucleos\UserBundle\Model\UserInterface;
use Sonata\AdminBundle\Admin\AdminInterface;

final class GlobalVariables
{
    /**
     * @var AdminInterface<UserInterface>|null
     */
    private ?AdminInterface $admin;

    /**
     * @phpstan-param AdminInterface<UserInterface> $admin
     */
    public function __construct(?AdminInterface $admin)
    {
        $this->admin = $admin;
    }

    /**
     * @return AdminInterface<UserInterface>
     */
    public function getUserAdmin(): AdminInterface
    {
        if (null === $this->admin) {
            throw new LogicException('No admin service is registered');
        }

        return $this->admin;
    }
}
