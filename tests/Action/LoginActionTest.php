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
use Nucleos\UserBundle\Model\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
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
     * @var MockObject|UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var AuthorizationCheckerInterface&MockObject
     */
    protected $authorizationChecker;

    /**
     * @var MockObject|Pool
     */
    protected $pool;

    /**
     * @var MockObject|TemplateRegistryInterface
     */
    protected $templateRegistry;

    /**
     * @var MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var MockObject|Session
     */
    protected $session;

    /**
     * @var CsrfTokenManagerInterface&MockObject
     */
    protected $csrfTokenManager;

    protected function setUp(): void
    {
        $this->templating           = $this->createMock(Environment::class);
        $this->eventDispatcher      = $this->createMock(EventDispatcherInterface::class);
        $this->urlGenerator         = $this->createMock(UrlGeneratorInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->pool                 = $this->createMock(Pool::class);
        $this->templateRegistry     = $this->createMock(TemplateRegistryInterface::class);
        $this->tokenStorage         = $this->createMock(TokenStorageInterface::class);
        $this->session              = $this->createMock(Session::class);
        $this->csrfTokenManager     = $this->createMock(CsrfTokenManagerInterface::class);
    }

    public function testAlreadyAuthenticated(): void
    {
        $request = new Request();

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

        $bag = $this->createMock(FlashBag::class);
        $bag->expects(static::once())
            ->method('add')
            ->with('nucleos_user_admin_error', 'nucleos_user_admin_already_authenticated')
        ;

        $this->session
            ->method('getFlashBag')
            ->willReturn($bag)
        ;

        $this->urlGenerator
            ->method('generate')
            ->with('sonata_admin_dashboard')
            ->willReturn('/foo')
        ;

        $action = $this->getAction();
        $result = $action($request);

        static::assertInstanceOf(RedirectResponse::class, $result);
        static::assertSame('/foo', $result->getTargetUrl());
    }

    /**
     * @dataProvider userGrantedAdminProvider
     */
    public function testUserGrantedAdmin(string $referer, string $expectedRedirectUrl): void
    {
        $session = $this->createMock(Session::class);
        $request = Request::create('http://some.url.com/exact-request-uri');
        $request->server->add(['HTTP_REFERER' => $referer]);
        $request->setSession($session);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn(null)
        ;

        $this->urlGenerator
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
    public function userGrantedAdminProvider(): array
    {
        return [
            ['', '/foo'],
            ['http://some.url.com/exact-request-uri', '/foo'],
            ['http://some.url.com', 'http://some.url.com'],
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
            'reset_route'   => '/foo',
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

        $this->urlGenerator
            ->method('generate')
            ->with('nucleos_user_admin_resetting_request')
            ->willReturn('/foo')
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
            ->with('@NucleosUserAdmin/Admin/Security/login.html.twig', $parameters)
            ->willReturn('template content')
        ;

        $action = $this->getAction();
        $result = $action($request);

        static::assertSame('template content', $result->getContent());
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
            $this->urlGenerator,
            $this->authorizationChecker,
            $this->pool,
            $this->templateRegistry,
            $this->tokenStorage,
            $this->session
        );
        $action->setCsrfTokenManager($this->csrfTokenManager);

        return $action;
    }
}
