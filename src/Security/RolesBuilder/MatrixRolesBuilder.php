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

namespace Nucleos\UserAdminBundle\Security\RolesBuilder;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class MatrixRolesBuilder implements MatrixRolesBuilderInterface
{
    private TokenStorageInterface $tokenStorage;

    private AdminRolesBuilderInterface $adminRolesBuilder;

    private ExpandableRolesBuilderInterface $securityRolesBuilder;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AdminRolesBuilderInterface $adminRolesBuilder,
        ExpandableRolesBuilderInterface $securityRolesBuilder
    ) {
        $this->tokenStorage         = $tokenStorage;
        $this->adminRolesBuilder    = $adminRolesBuilder;
        $this->securityRolesBuilder = $securityRolesBuilder;
    }

    /**
     * @return mixed[]
     */
    public function getRoles(?string $domain = null): array
    {
        if (null === $this->tokenStorage->getToken()) {
            return [];
        }

        return array_merge(
            $this->securityRolesBuilder->getRoles($domain),
            $this->adminRolesBuilder->getRoles($domain)
        );
    }

    /**
     * @return mixed[]
     */
    public function getExpandedRoles(?string $domain = null): array
    {
        if (null === $this->tokenStorage->getToken()) {
            return [];
        }

        return array_merge(
            $this->securityRolesBuilder->getExpandedRoles($domain),
            $this->adminRolesBuilder->getRoles($domain)
        );
    }

    /**
     * @return string[]
     */
    public function getPermissionLabels(): array
    {
        return $this->adminRolesBuilder->getPermissionLabels();
    }
}
