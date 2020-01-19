<?php

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserAdminBundle\Security;

interface EditableRolesBuilderInterface
{
    /**
     * @param bool|string|null $domain
     *
     * @return string[]
     */
    public function getRoles($domain = false, bool $expanded = true): array;

    /**
     * @param bool|string|null $domain
     *
     * @return string[]
     */
    public function getRolesReadOnly($domain = false): array;
}
