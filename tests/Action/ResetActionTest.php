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

use Closure;
use Nucleos\UserAdminBundle\Action\ResetAction;
use Nucleos\UserAdminBundle\Tests\Fixtures\PoolMockFactory;
use Nucleos\UserBundle\Model\User;
use Nucleos\UserBundle\Model\UserManager;
use Nucleos\UserBundle\Security\LoginManager;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class ResetActionTest extends TestCase
{
    /**
     * @var Environment&MockObject
     */
    protected $templating;

    /**
     * @var MockObject&RouterInterface
     */
    protected $router;

    /**
     * @var AuthorizationCheckerInterface&MockObject
     */
    protected $authorizationChecker;

    protected Pool $pool;

    /**
     * @var MockObject&TemplateRegistryInterface
     */
    protected $templateRegistry;

    /**
     * @var FormFactoryInterface&MockObject
     */
    protected $formFactory;

    /**
     * @var MockObject&UserManager
     */
    protected $userManager;

    /**
     * @var LoginManager&MockObject
     */
    protected $loginManager;

    /**
     * @var MockObject&TranslatorInterface
     */
    protected $translator;

    /**
     * @var MockObject&Session
     */
    protected $session;

    protected int $resetTtl;

    protected string $firewallName;

    protected function setUp(): void
    {
        $this->templating           = $this->createMock(Environment::class);
        $this->router               = $this->createMock(RouterInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->pool                 = PoolMockFactory::create();
        $this->templateRegistry     = $this->createMock(TemplateRegistryInterface::class);
        $this->formFactory          = $this->createMock(FormFactoryInterface::class);
        $this->userManager          = $this->createMock(UserManager::class);
        $this->loginManager         = $this->createMock(LoginManager::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->session              = $this->createMock(Session::class);
        $this->resetTtl             = 60;
        $this->firewallName         = 'default';
    }

    public function testAuthenticated(): void
    {
        $request = new Request();

        $this->authorizationChecker->expects(static::once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $this->router
            ->method('generate')
            ->with('sonata_admin_dashboard')
            ->willReturn('/foo')
        ;

        $action = $this->getAction();
        $result = $action($request, 'token');

        static::assertInstanceOf(RedirectResponse::class, $result);
        static::assertSame('/foo', $result->getTargetUrl());
    }

    public function testUnknownToken(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('The user with "confirmation token" does not exist for value "token"');

        $request = new Request();

        $this->userManager
            ->method('findUserByConfirmationToken')
            ->with('token')
            ->willReturn(null)
        ;

        $action = $this->getAction();
        $action($request, 'token');
    }

    public function testPasswordRequestNonExpired(): void
    {
        $request = new Request();

        $user = $this->createMock(User::class);
        $user
            ->method('isPasswordRequestNonExpired')
            ->willReturn(false)
        ;

        $this->userManager
            ->method('findUserByConfirmationToken')
            ->with('token')
            ->willReturn($user)
        ;

        $this->router
            ->method('generate')
            ->with('nucleos_user_admin_resetting_request')
            ->willReturn('/foo')
        ;

        $action = $this->getAction();
        $result = $action($request, 'token');

        static::assertInstanceOf(RedirectResponse::class, $result);
        static::assertSame('/foo', $result->getTargetUrl());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testReset(): void
    {
        $request = new Request();

        $user = $this->createMock(User::class);
        $user
            ->method('isPasswordRequestNonExpired')
            ->willReturn(true)
        ;

        $view = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
        $form
            ->method('isValid')
            ->willReturn(true)
        ;
        $form
            ->method('isSubmitted')
            ->willReturn(false)
        ;
        $form->expects(static::once())
            ->method('createView')
            ->willReturn($view)
        ;

        $this->userManager
            ->method('findUserByConfirmationToken')
            ->with('user-token')
            ->willReturn($user)
        ;

        $this->formFactory->expects(static::once())
            ->method('create')
            ->willReturn($form)
        ;

        $this->router
            ->method('generate')
            ->with('nucleos_user_admin_security_check')
            ->willReturn('/check')
        ;

        $this->templating
            ->method('render')
            ->with('@NucleosUserAdmin/Admin/Security/Resetting/reset.html.twig', [
                'token'         => 'user-token',
                'form'          => $view,
                'base_template' => 'base.html.twig',
                'admin_pool'    => $this->pool,
            ])
            ->willReturn('template content')
        ;

        $this->templateRegistry
            ->method('getTemplate')
            ->with('layout')
            ->willReturn('base.html.twig')
        ;

        $action = $this->getAction();
        $result = $action($request, 'user-token');

        static::assertSame('template content', $result->getContent());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testPostedReset(): void
    {
        $request = new Request();

        $user = $this->createMock(User::class);
        $user
            ->method('isPasswordRequestNonExpired')
            ->willReturn(true)
        ;
        $user->expects(static::once())
            ->method('setLastLogin')
        ;
        $user->expects(static::once())
            ->method('setConfirmationToken')
            ->with(null)
        ;
        $user->expects(static::once())
            ->method('setPasswordRequestedAt')
            ->with(null)
        ;
        $user->expects(static::once())
            ->method('setEnabled')
            ->with(true)
        ;

        $form = $this->createMock(FormInterface::class);
        $form
            ->method('isValid')
            ->willReturn(true)
        ;
        $form
            ->method('isSubmitted')
            ->willReturn(true)
        ;

        $this->translator
            ->method('trans')
            ->willReturnCallback(
                static function (string $message): string {
                    return $message;
                }
            )
        ;

        $bag = $this->createMock(FlashBag::class);
        $bag->expects(static::once())
            ->method('add')
            ->with('success', 'resetting.flash.success')
        ;

        $this->session
            ->method('getFlashBag')
            ->willReturn($bag)
        ;

        $this->userManager
            ->method('findUserByConfirmationToken')
            ->with('token')
            ->willReturn($user)
        ;
        $this->userManager->expects(static::once())
            ->method('updateUser')
            ->with($user)
        ;

        $this->loginManager->expects(static::once())
            ->method('logInUser')
            ->with('default', $user, static::isInstanceOf(Response::class))
        ;

        $this->formFactory->expects(static::once())
            ->method('create')
            ->willReturn($form)
        ;

        $this->router->expects($matcher = static::exactly(2))->method('generate')
            ->willReturnCallback($this->withParameter($matcher, [
                ['nucleos_user_admin_security_check', ['token' => 'token']],
                ['sonata_admin_dashboard'],
            ]))
            ->willReturnOnConsecutiveCalls('/check', '/dashboard')
        ;

        $action = $this->getAction();
        $result = $action($request, 'token');

        static::assertInstanceOf(RedirectResponse::class, $result);
        static::assertSame('/dashboard', $result->getTargetUrl());
    }

    /**
     * @param array<array-key, mixed[]> $parameters
     */
    protected function withParameter(InvokedCount $matcher, array $parameters): Closure
    {
        return static function () use ($matcher, $parameters): void {
            /** @psalm-suppress InternalMethod */
            $callNumber = $matcher->numberOfInvocations();

            Assert::assertEquals($parameters[$callNumber-1], \func_get_args(), sprintf('Call %s', $callNumber));
        };
    }

    private function getAction(): ResetAction
    {
        return new ResetAction(
            $this->templating,
            $this->router,
            $this->authorizationChecker,
            $this->pool,
            $this->templateRegistry,
            $this->formFactory,
            $this->userManager,
            $this->loginManager,
            $this->translator,
            $this->session,
            $this->resetTtl,
            $this->firewallName
        );
    }
}
