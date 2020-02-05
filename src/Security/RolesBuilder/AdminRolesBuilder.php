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

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AdminRolesBuilder implements AdminRolesBuilderInterface
{
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
     * @var string []
     */
    private $excludeAdmins = [];

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        Pool $pool,
        TranslatorInterface $translator
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->pool                 = $pool;
        $this->translator           = $translator;
    }

    public function getPermissionLabels(): array
    {
        $permissionLabels = [];
        foreach ($this->getRoles() as $attributes) {
            if (isset($attributes['label'])) {
                $permissionLabels[$attributes['label']] = $attributes['label'];
            }
        }

        return $permissionLabels;
    }

    /**
     * @return string[]
     */
    public function getExcludeAdmins(): array
    {
        return $this->excludeAdmins;
    }

    public function addExcludeAdmin(string $exclude): void
    {
        $this->excludeAdmins[] = $exclude;
    }

    public function getRoles(string $domain = null): array
    {
        $adminRoles = [];
        foreach ($this->pool->getAdminServiceIds() as $id) {
            if (\in_array($id, $this->excludeAdmins, true)) {
                continue;
            }

            $admin           = $this->pool->getInstance($id);
            $securityHandler = $admin->getSecurityHandler();

            if (null === $securityHandler) {
                continue;
            }

            $baseRole = $securityHandler->getBaseRole($admin);

            foreach (array_keys($admin->getSecurityInformation()) as $key) {
                $role              = sprintf($baseRole, $key);

                $adminRoles[$role] = [
                    'role'            => $role,
                    'label'           => $key,
                    'role_translated' => $this->translateRole($role, $domain),
                    'is_granted'      => $this->isMaster($admin) || $this->authorizationChecker->isGranted($role),
                    'admin_label'     => $this->getAdminLabel($admin),
                ];
            }
        }

        return $adminRoles;
    }

    private function getAdminLabel(AdminInterface $admin): string
    {
        return sprintf(
            '%s > %s',
            $this->getGroupLabel($admin),
            $admin->getTranslator()->trans($admin->getLabel(), [], $admin->getTranslationDomain())
        );
    }

    private function getGroupLabel(AdminInterface $admin): string
    {
        foreach ($this->pool->getAdminGroups() as $groupName => $groupData) {
            if (!\is_array($groupData['items'])) {
                continue;
            }

            foreach ($groupData['items'] as $item) {
                if ($item['admin'] === $admin->getCode()) {
                    return $admin->getTranslator()->trans($groupName, [], 'SonataAdminBundle');
                }
            }
        }

        if (null !== $admin->getParent() && $admin->getParent() !== $admin) {
            return $this->getGroupLabel($admin->getParent());
        }

        return $this->guessGroupLabel($admin);
    }

    private function isMaster(AdminInterface $admin): bool
    {
        return $admin->isGranted('MASTER')                                          ||
            $admin->isGranted('OPERATOR')                                           ||
            $this->authorizationChecker->isGranted($this->pool->getOption('role_super_admin'));
    }

    private function translateRole(string $role, ?string $domain): string
    {
        if (null !== $domain) {
            return $this->translator->trans($role, [], $domain);
        }

        return $role;
    }

    private function guessGroupLabel(AdminInterface $admin): string
    {
        $baseRoute = $admin->getBaseCodeRoute();

        $group = substr($baseRoute, 0, (int) strpos($baseRoute, '.'));

        return $admin->getTranslator()->trans($group, [], 'SonataAdminBundle');
    }
}
