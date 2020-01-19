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

namespace Nucleos\UserAdminBundle\Security;

use Exception;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EditableRolesBuilder implements EditableRolesBuilderInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var Pool
     */
    private $pool;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $rolesHierarchy;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        Pool $pool,
        array $rolesHierarchy = []
    ) {
        $this->tokenStorage         = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->pool                 = $pool;
        $this->rolesHierarchy       = $rolesHierarchy;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function getRoles($domain = false, bool $expanded = true): array
    {
        $roles = [];

        if (null === $this->tokenStorage->getToken()) {
            return $roles;
        }

        $this->iterateAdminRoles(
            function ($role, $isMaster) use ($domain, &$roles): void {
                if ($isMaster) {
                    // if the user has the MASTER permission, allow to grant access the admin roles to other users
                    $roles[$role] = $this->translateRole($role, $domain);
                }
            }
        );

        $isMaster = $this->authorizationChecker->isGranted(
            $this->pool->getOption('role_super_admin', 'ROLE_SUPER_ADMIN')
        );

        // get roles from the service container
        foreach ($this->rolesHierarchy as $name => $rolesHierarchy) {
            if ($this->authorizationChecker->isGranted($name) || $isMaster) {
                $roles[$name] = $this->translateRole($name, $domain);
                if ($expanded) {
                    $result       = array_map(
                        [$this, 'translateRole'],
                        $rolesHierarchy,
                        array_fill(0, \count($rolesHierarchy), $domain)
                    );
                    $roles[$name] .= ': '.implode(', ', $result);
                }
                foreach ($rolesHierarchy as $role) {
                    if (!isset($roles[$role])) {
                        $roles[$role] = $this->translateRole($role, $domain);
                    }
                }
            }
        }

        return $roles;
    }

    public function getRolesReadOnly($domain = false): array
    {
        $rolesReadOnly = [];

        if (null === $this->tokenStorage->getToken()) {
            return $rolesReadOnly;
        }

        $this->iterateAdminRoles(
            function ($role, $isMaster) use ($domain, &$rolesReadOnly): void {
                if (!$isMaster && $this->authorizationChecker->isGranted($role)) {
                    // although the user has no MASTER permission, allow the currently logged in user to view the role
                    $rolesReadOnly[$role] = $this->translateRole($role, $domain);
                }
            }
        );

        return $rolesReadOnly;
    }

    private function iterateAdminRoles(callable $func): void
    {
        // get roles from the Admin classes
        foreach ($this->pool->getAdminServiceIds() as $id) {
            try {
                $admin = $this->pool->getInstance($id);
            } catch (Exception $e) {
                continue;
            }

            $isMaster        = $admin->isGranted('MASTER');
            $securityHandler = $admin->getSecurityHandler();

            if (null === $securityHandler) {
                continue;
            }

            // TODO get the base role from the admin or security handler
            $baseRole = $securityHandler->getBaseRole($admin);

            if ('' === $baseRole) {
                continue;
            }

            foreach ($admin->getSecurityInformation() as $role => $permissions) {
                $role = sprintf($baseRole, $role);
                \call_user_func($func, $role, $isMaster, $permissions);
            }
        }
    }

    /**
     * @param bool|string|null $domain
     */
    private function translateRole(string $role, $domain): string
    {
        // translation domain is false, do not translate it,
        // null is fallback to message domain
        if (\is_bool($domain) || !isset($this->translator)) {
            return $role;
        }

        return $this->translator->trans($role, [], $domain);
    }
}
