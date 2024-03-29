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

namespace Nucleos\UserAdminBundle\Tests\Security\Authorization\Voter;

use Nucleos\UserAdminBundle\Security\Authorization\Voter\UserAclVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserAclVoterTest extends TestCase
{
    public function testVoteWillAbstainWhenAUserIsLoggedInAndASuperAdmin(): void
    {
        // Given
        $user = $this->createMock(\Nucleos\UserBundle\Model\UserInterface::class);
        $user->method('isSuperAdmin')->willReturn(true);

        $loggedInUser = $this->createMock(\Nucleos\UserBundle\Model\UserInterface::class);
        $loggedInUser->method('isSuperAdmin')->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUser);

        $voter = new UserAclVoter();

        // When
        $decision = $voter->vote($token, $user, ['EDIT']);

        // Then
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $decision, 'Should abstain from voting');
    }

    public function testVoteWillDenyAccessWhenAUserIsLoggedInAndNotASuperAdmin(): void
    {
        // Given
        $user = $this->createMock(\Nucleos\UserBundle\Model\UserInterface::class);
        $user->method('isSuperAdmin')->willReturn(true);

        $loggedInUser = $this->createMock(\Nucleos\UserBundle\Model\UserInterface::class);
        $loggedInUser->method('isSuperAdmin')->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUser);

        $voter = new UserAclVoter();

        // When
        $decision = $voter->vote($token, $user, ['EDIT']);

        // Then
        self::assertSame(VoterInterface::ACCESS_DENIED, $decision, 'Should deny access');
    }

    public function testVoteWillAbstainWhenAUserIsNotAvailable(): void
    {
        // Given
        $user = $this->createMock(\Nucleos\UserBundle\Model\UserInterface::class);
        $user->method('isSuperAdmin')->willReturn(true);

        $loggedInUser = null;

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUser);

        $voter = new UserAclVoter();

        // When
        $decision = $voter->vote($token, $user, ['EDIT']);

        // Then
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $decision, 'Should abstain from voting');
    }

    public function testVoteWillAbstainWhenAUserIsLoggedInButIsNotAValidUser(): void
    {
        // Given
        $user = $this->createMock(\Nucleos\UserBundle\Model\UserInterface::class);
        $user->method('isSuperAdmin')->willReturn(true);

        $loggedInUser = $this->createMock(UserInterface::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($loggedInUser);

        $voter = new UserAclVoter();

        // When
        $decision = $voter->vote($token, $user, ['EDIT']);

        // Then
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $decision, 'Should abstain from voting');
    }
}
