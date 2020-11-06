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
use Nucleos\UserBundle\Mailer\MailerInterface;
use Nucleos\UserBundle\Model\User;
use Nucleos\UserBundle\Model\UserManagerInterface;
use Nucleos\UserBundle\Util\TokenGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class SendEmailActionTest extends TestCase
{
    /**
     * @var MockObject|UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var MockObject|Pool
     */
    protected $pool;

    /**
     * @var MockObject|TemplateRegistryInterface
     */
    protected $templateRegistry;

    /**
     * @var MockObject|UserManagerInterface
     */
    protected $userManager;

    /**
     * @var MailerInterface&MockObject
     */
    protected $mailer;

    /**
     * @var MockObject|TokenGeneratorInterface
     */
    protected $tokenGenerator;

    /**
     * @var int
     */
    protected $resetTtl;

    /**
     * @var string
     */
    protected $fromEmail;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var ContainerBuilder&MockObject
     */
    protected $container;

    /**
     * @var Environment&MockObject
     */
    protected $templating;

    protected function setUp(): void
    {
        $this->templating       = $this->createMock(Environment::class);
        $this->urlGenerator     = $this->createMock(UrlGeneratorInterface::class);
        $this->pool             = $this->createMock(Pool::class);
        $this->templateRegistry = $this->createMock(TemplateRegistryInterface::class);
        $this->userManager      = $this->createMock(UserManagerInterface::class);
        $this->mailer           = $this->createMock(MailerInterface::class);
        $this->tokenGenerator   = $this->createMock(TokenGeneratorInterface::class);
        $this->resetTtl         = 60;
        $this->fromEmail        = 'noreply@localhost';
        $this->template         = 'email.txt.twig';
        $this->container        = $this->createMock(ContainerBuilder::class);
    }

    public function testUnknownUsername(): void
    {
        $request = new Request([], ['username' => 'bar']);

        $parameters = [
            'base_template'    => 'base.html.twig',
            'admin_pool'       => $this->pool,
            'invalid_username' => 'bar',
        ];

        $this->templating->expects(static::once())
            ->method('render')
            ->with('@NucleosUserAdmin/Admin/Security/Resetting/request.html.twig', $parameters)
            ->willReturn('template content')
        ;

        $this->templateRegistry
            ->method('getTemplate')
            ->with('layout')
            ->willReturn('base.html.twig')
        ;

        $this->userManager
            ->method('findUserByUsernameOrEmail')
            ->with('bar')
            ->willReturn(null)
        ;

        $action = $this->getAction();
        $result = $action($request);

        static::assertSame('template content', $result->getContent());
    }

    public function testPasswordRequestNonExpired(): void
    {
        $request = new Request([], ['username' => 'bar']);

        $user = $this->createMock(User::class);
        $user
            ->method('isPasswordRequestNonExpired')
            ->willReturn(true)
        ;

        $this->userManager
            ->method('findUserByUsernameOrEmail')
            ->with('bar')
            ->willReturn($user)
        ;

        $this->mailer->expects(static::never())
            ->method('sendResettingEmailMessage')
        ;

        $this->urlGenerator
            ->method('generate')
            ->with('nucleos_user_admin_resetting_check_email')
            ->willReturn('/foo')
        ;

        $action = $this->getAction();
        $result = $action($request);

        static::assertInstanceOf(RedirectResponse::class, $result);
        static::assertSame('/foo', $result->getTargetUrl());
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

        $this->userManager
            ->method('findUserByUsernameOrEmail')
            ->with('bar')
            ->willReturn($user)
        ;

        $this->mailer->expects(static::never())
            ->method('sendResettingEmailMessage')
        ;

        $this->urlGenerator
            ->method('generate')
            ->with('nucleos_user_admin_resetting_request')
            ->willReturn('/foo')
        ;

        $action = $this->getAction();
        $result = $action($request);

        static::assertInstanceOf(RedirectResponse::class, $result);
        static::assertSame('/foo', $result->getTargetUrl());
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

        $this->userManager
            ->method('findUserByUsernameOrEmail')
            ->with('bar')
            ->willReturn($user)
        ;

        $this->tokenGenerator->expects(static::once())
            ->method('generateToken')
            ->willReturn('user-token')
        ;

        $this->mailer->expects(static::once())
            ->method('sendResettingEmailMessage')
        ;

        $this->urlGenerator
            ->method('generate')
            ->withConsecutive(
                ['nucleos_user_admin_resetting_check_email', ['username' => 'bar']]
            )
            ->willReturnOnConsecutiveCalls(
                '/check-email'
            )
        ;

        $action = $this->getAction();
        $result = $action($request);

        static::assertInstanceOf(RedirectResponse::class, $result);
        static::assertSame('/check-email', $result->getTargetUrl());
    }

    private function getAction(): SendEmailAction
    {
        return new SendEmailAction(
            $this->templating,
            $this->urlGenerator,
            $this->pool,
            $this->templateRegistry,
            $this->userManager,
            $this->mailer,
            $this->tokenGenerator,
            $this->resetTtl
        );
    }
}
