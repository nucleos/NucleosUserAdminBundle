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

namespace Nucleos\UserAdminBundle\Tests\Action;

use Nucleos\UserAdminBundle\Action\SendEmailAction;
use Nucleos\UserBundle\Mailer\ResettingMailer;
use Nucleos\UserBundle\Model\User;
use Nucleos\UserBundle\Model\UserInterface;
use Nucleos\UserBundle\Model\UserManager;
use Nucleos\UserBundle\Util\TokenGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class SendEmailActionTest extends TestCase
{
    /**
     * @var MockObject&UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var MockObject&UserManager
     */
    protected $userManager;

    /**
     * @var MockObject&ResettingMailer
     */
    protected $mailer;

    /**
     * @var MockObject&TokenGenerator
     */
    protected $tokenGenerator;

    /**
     * @var MockObject&UserProviderInterface<UserInterface>
     */
    protected $userProvider;

    protected int $resetTtl;

    protected string $fromEmail;

    /**
     * @var ContainerBuilder&MockObject
     */
    protected $container;

    protected function setUp(): void
    {
        $this->urlGenerator     = $this->createMock(UrlGeneratorInterface::class);
        $this->userManager      = $this->createMock(UserManager::class);
        $this->mailer           = $this->createMock(ResettingMailer::class);
        $this->tokenGenerator   = $this->createMock(TokenGenerator::class);

        if (!method_exists(UserProviderInterface::class, 'loadUserByIdentifier')) {
            $this->userProvider     = $this->getMockBuilder(UserProviderInterface::class)
                ->addMethods(['loadUserByIdentifier'])
                ->getMockForAbstractClass()
            ;
        } else {
            $this->userProvider     = $this->createMock(UserProviderInterface::class);
        }

        $this->resetTtl         = 60;
        $this->fromEmail        = 'noreply@localhost';
        $this->container        = $this->createMock(ContainerBuilder::class);
    }

    public function testUnknownUsername(): void
    {
        $request = new Request([], ['username' => 'bar']);

        $this->userProvider
            ->method('loadUserByIdentifier')
            ->with('bar')
            ->willThrowException(new UserNotFoundException())
        ;

        $this->urlGenerator
            ->method('generate')
            ->with('nucleos_user_admin_resetting_check_email', ['username' => 'bar'])
            ->willReturn('/check-email')
        ;

        $action = $this->getAction();
        $result = $action($request);

        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertSame('/check-email', $result->getTargetUrl());
    }

    public function testPasswordRequestNonExpired(): void
    {
        $request = new Request([], ['username' => 'bar']);

        $user = $this->createMock(User::class);
        $user
            ->method('isPasswordRequestNonExpired')
            ->willReturn(true)
        ;

        $this->userProvider
            ->method('loadUserByIdentifier')
            ->with('bar')
            ->willReturn($user)
        ;

        $this->mailer->expects(self::never())
            ->method('sendResettingEmailMessage')
        ;

        $this->urlGenerator
            ->method('generate')
            ->with('nucleos_user_admin_resetting_check_email')
            ->willReturn('/foo')
        ;

        $action = $this->getAction();
        $result = $action($request);

        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertSame('/foo', $result->getTargetUrl());
    }

    public function testAccountLocked(): void
    {
        $request = new Request([], ['username' => 'bar']);

        $user = $this->createMock(User::class);
        $user
            ->method('isPasswordRequestNonExpired')
            ->willReturn(false)
        ;
        $user
            ->method('isAccountNonLocked')
            ->willReturn(false)
        ;

        $this->userProvider
            ->method('loadUserByIdentifier')
            ->with('bar')
            ->willReturn($user)
        ;

        $this->mailer->expects(self::never())
            ->method('sendResettingEmailMessage')
        ;

        $this->urlGenerator
            ->method('generate')
            ->with('nucleos_user_admin_resetting_request')
            ->willReturn('/foo')
        ;

        $action = $this->getAction();
        $result = $action($request);

        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertSame('/foo', $result->getTargetUrl());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testEmailSent(): void
    {
        $request = new Request([], ['username' => 'bar']);

        $storedToken = null;

        $user = $this->createMock(User::class);
        $user
            ->method('getEmail')
            ->willReturn('user@localhost')
        ;
        $user
            ->method('isPasswordRequestNonExpired')
            ->willReturn(false)
        ;
        $user
            ->method('isAccountNonLocked')
            ->willReturn(true)
        ;
        $user
            ->method('setConfirmationToken')
            ->willReturnCallback(
                static function (?string $token) use (&$storedToken): void {
                    $storedToken = $token;
                }
            )
        ;
        $user
            ->method('getConfirmationToken')
            ->willReturnCallback(
                static function () use (&$storedToken): ?string {
                    return $storedToken;
                }
            )
        ;

        $this->userProvider
            ->method('loadUserByIdentifier')
            ->with('bar')
            ->willReturn($user)
        ;

        $this->tokenGenerator->expects(self::once())
            ->method('generateToken')
            ->willReturn('user-token')
        ;

        $this->mailer->expects(self::once())
            ->method('sendResettingEmailMessage')
        ;

        $this->urlGenerator
            ->method('generate')
            ->with('nucleos_user_admin_resetting_check_email', ['username' => 'bar'])
            ->willReturn('/check-email')
        ;

        $action = $this->getAction();
        $result = $action($request);

        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertSame('/check-email', $result->getTargetUrl());
    }

    private function getAction(): SendEmailAction
    {
        return new SendEmailAction(
            $this->urlGenerator,
            $this->userManager,
            $this->mailer,
            $this->tokenGenerator,
            $this->userProvider,
            $this->resetTtl
        );
    }
}
