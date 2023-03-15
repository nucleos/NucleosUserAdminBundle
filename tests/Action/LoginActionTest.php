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
use Nucleos\UserAdminBundle\Action\LoginAction;
use Nucleos\UserAdminBundle\Tests\Fixtures\PoolMockFactory;
use Nucleos\UserBundle\Model\UserInterface;
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
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class LoginActionTest extends TestCase
{
    /**
     * @var Environment&MockObject
     */
    protected $templating;

    /**
     * @var EventDispatcherInterface&MockObject
     */
    protected $eventDispatcher;

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
     * @var MockObject&TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var CsrfTokenManagerInterface&MockObject
     */
    protected $csrfTokenManager;

    /**
     * @var FormFactoryInterface&MockObject
     */
    protected $formFactory;

    /**
     * @var MockObject&TranslatorInterface
     */
    protected $translator;

    protected function setUp(): void
    {
        $this->templating           = $this->createMock(Environment::class);
        $this->eventDispatcher      = $this->createMock(EventDispatcherInterface::class);
        $this->router               = $this->createMock(RouterInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->pool                 = PoolMockFactory::create();
        $this->templateRegistry     = $this->createMock(TemplateRegistryInterface::class);
        $this->tokenStorage         = $this->createMock(TokenStorageInterface::class);
        $this->csrfTokenManager     = $this->createMock(CsrfTokenManagerInterface::class);
        $this->formFactory          = $this->createMock(FormFactoryInterface::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAlreadyAuthenticated(): void
    {
        $user = $this->createMock(UserInterface::class);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->translator
            ->method('trans')
            ->willReturnCallback(
                static function (string $message): string {
                    return 'trans.'.$message;
                }
            )
        ;

        $session = new Session();

        $request = new Request();
        $request->setSession($session);

        $this->router
            ->method('generate')
            ->with('sonata_admin_dashboard')
            ->willReturn('/foo')
        ;

        $action = $this->getAction();
        $result = $action($request);

        static::assertInstanceOf(RedirectResponse::class, $result);
        static::assertSame('/foo', $result->getTargetUrl());

        static::assertSame([
            'sonata_flash_info' => ['trans.nucleos_user_admin_already_authenticated'],
        ], $session->getFlashBag()->all());
    }

    /**
     * @dataProvider userGrantedAdminProvider
     */
    public function testUserGrantedAdmin(string $referer, string $expectedRedirectUrl): void
    {
        $session = $this->createMock(Session::class);
        $request = Request::create('https://some.url.com/exact-request-uri');
        $request->server->add(['HTTP_REFERER' => $referer]);
        $request->setSession($session);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn(null)
        ;

        $this->router
            ->method('generate')
            ->with('sonata_admin_dashboard')
            ->willReturn('/foo')
        ;

        $this->authorizationChecker->expects(static::once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true)
        ;

        $action = $this->getAction();
        $result = $action($request);

        static::assertInstanceOf(RedirectResponse::class, $result);
        static::assertSame($expectedRedirectUrl, $result->getTargetUrl());
    }

    /**
     * @return string[][]
     */
    public static function userGrantedAdminProvider(): array
    {
        return [
            ['', '/foo'],
            ['https://some.url.com/exact-request-uri', '/foo'],
            ['https://some.url.com', 'https://some.url.com'],
        ];
    }

    /**
     * @dataProvider unauthenticatedProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUnauthenticated(string $lastUsername, AuthenticationException $errorMessage = null): void
    {
        $session           = $this->createMock(Session::class);
        $sessionParameters = [
            '_security.last_error'    => $errorMessage,
            '_security.last_username' => $lastUsername,
        ];
        $session
            ->method('get')
            ->willReturnCallback(
                static function (string $key) use ($sessionParameters) {
                    return $sessionParameters[$key] ?? null;
                }
            )
        ;
        $session
            ->method('has')
            ->willReturnCallback(
                static function (string $key) use ($sessionParameters): bool {
                    return isset($sessionParameters[$key]);
                }
            )
        ;
        $request = new Request();
        $request->setSession($session);

        $csrfToken = $this->createMock(CsrfToken::class);
        $csrfToken
            ->method('getValue')
            ->willReturn('csrf-token')
        ;

        $this->tokenStorage
            ->method('getToken')
            ->willReturn(null)
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
        $form
            ->method('add')
            ->willReturnSelf()
        ;
        $form->expects(static::once())
            ->method('createView')
            ->willReturn($view)
        ;

        $this->formFactory->expects(static::once())
            ->method('create')
            ->willReturn($form)
        ;

        $this->router->expects($matcher = static::exactly(2))->method('generate')
            ->willReturnCallback($this->withParameter($matcher, [
                ['nucleos_user_admin_security_check'],
                ['sonata_admin_dashboard'],
            ]))
            ->willReturnOnConsecutiveCalls('/check', '/reset')
        ;

        $this->authorizationChecker->expects(static::once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false)
        ;

        $this->csrfTokenManager
            ->method('getToken')
            ->with('authenticate')
            ->willReturn($csrfToken)
        ;

        $this->templateRegistry
            ->method('getTemplate')
            ->with('layout')
            ->willReturn('base.html.twig')
        ;

        $this->templating
            ->method('render')
            ->with('@NucleosUserAdmin/Admin/Security/login.html.twig', [
                'admin_pool'    => $this->pool,
                'base_template' => 'base.html.twig',
                'csrf_token'    => 'csrf-token',
                'error'         => $errorMessage,
                'last_username' => $lastUsername,
                'reset_route'   => '/reset',
                'form'          => $view,
            ])
            ->willReturn('template content')
        ;

        $action = $this->getAction();
        $result = $action($request);

        static::assertSame('template content', $result->getContent());
    }

    public static function unauthenticatedProvider(): array
    {
        $error = new AuthenticationException('An error');

        return [
            ['', null],
            ['FooUser', $error],
        ];
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

    private function getAction(): LoginAction
    {
        $action = new LoginAction(
            $this->templating,
            $this->eventDispatcher,
            $this->router,
            $this->authorizationChecker,
            $this->pool,
            $this->templateRegistry,
            $this->tokenStorage,
            $this->formFactory,
            null,
            $this->translator
        );
        $action->setCsrfTokenManager($this->csrfTokenManager);

        return $action;
    }
}
