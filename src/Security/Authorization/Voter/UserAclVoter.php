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

namespace Nucleos\UserAdminBundle\Security\Authorization\Voter;

use Nucleos\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class UserAclVoter implements VoterInterface, CacheableVoterInterface
{
    /**
     * @deprecated without any replace
     *
     * @param mixed $class
     */
    public function supportsClass($class): bool
    {
        return is_subclass_of($class, UserInterface::class);
    }

    public function supportsType(string $subjectType): bool
    {
        return is_subclass_of($subjectType, UserInterface::class);
    }

    public function supportsAttribute(string $attribute): bool
    {
        return 'EDIT' === $attribute || 'DELETE' === $attribute;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        if (!$this->isValidSubject($subject)) {
            return self::ACCESS_ABSTAIN;
        }

        $user = $token->getUser();

        // deny a non super admin user to edit or delete a super admin user
        if ($subject instanceof UserInterface && $user instanceof UserInterface) {
            foreach ($attributes as $attribute) {
                if ($this->supportsAttribute($attribute) && $subject->isSuperAdmin() && !$user->isSuperAdmin()) {
                    return self::ACCESS_DENIED;
                }
            }
        }

        // leave the permission voting to the AclVoter that is using the default permission map
        return self::ACCESS_ABSTAIN;
    }

    private function isValidSubject(mixed $subject): bool
    {
        return \is_object($subject) && $this->supportsType(\get_class($subject));
    }
}
