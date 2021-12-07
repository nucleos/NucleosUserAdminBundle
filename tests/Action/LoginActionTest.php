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

use Nucleos\UserAdminBundle\Action\LoginAction;
use Nucleos\UserAdminBundle\Tests\Fixtures\PoolMockFactory;
use Nucleos\UserBundle\Model\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
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
     * @var MockObject&FormFactoryInterface
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
                    return $message;
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

        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertSame('/foo', $result->getTargetUrl());

        self::assertSame([
            'sonata_flash_info' => ['nucleos_user_admin_already_authenticated'],
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

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true)
        ;

        $action = $this->getAction();
        $result = $action($request);

        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertSame($expectedRedirectUrl, $result->getTargetUrl());
    }

    /**
     * @return string[][]
     */
    public function userGrantedAdminProvider(): array
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

        $parameters = [
            'admin_pool'    => $this->pool,
            'base_template' => 'base.html.twig',
            'csrf_token'    => 'csrf-token',
            'error'         => $errorMessage,
            'last_username' => $lastUsername,
            'reset_route'   => '/reset',
            'form'          => 'Form View',
        ];

        $csrfToken = $this->createMock(CsrfToken::class);
        $csrfToken
            ->method('getValue')
            ->willReturn('csrf-token')
        ;

        $this->tokenStorage
            ->method('getToken')
            ->willReturn(null)
        ;

        $form = $this->createMock(Form::class);
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
        $form->expects(self::once())
            ->method('createView')
            ->willReturn('Form View')
        ;

        $this->formFactory->expects(self::once())
            ->method('create')
            ->willReturn($form)
        ;

        $this->router
            ->method('generate')
            ->withConsecutive([
                'nucleos_user_admin_security_check',
            ], [
                'nucleos_user_admin_resetting_request',
            ])
            ->willReturn('/check', '/reset')
        ;

        $this->authorizationChecker->expects(self::once())
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
            ->with('@NucleosUserAdmin/Admin/Security/login.html.twig', $parameters)
            ->willReturn('template content')
        ;

        $action = $this->getAction();
        $result = $action($request);

        self::assertSame('template content', $result->getContent());
    }

    public function unauthenticatedProvider(): array
    {
        $error = new AuthenticationException('An error');

        return [
            ['', null],
            ['FooUser', $error],
        ];
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
