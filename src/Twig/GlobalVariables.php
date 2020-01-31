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

use Sonata\AdminBundle\Admin\AdminInterface;

final class GlobalVariables
{
    /**
     * @var AdminInterface
     */
    private $admin;

    public function __construct(AdminInterface $admin)
    {
        $this->admin              = $admin;
    }

    public function getUserAdmin(): AdminInterface
    {
        return $this->admin;
    }
}
