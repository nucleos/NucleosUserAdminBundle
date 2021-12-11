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

use Nucleos\UserAdminBundle\Action\RequestAction;
use Nucleos\UserAdminBundle\Tests\Fixtures\PoolMockFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

final class RequestActionTest extends TestCase
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
    private $formFactory;

    protected function setUp(): void
    {
        $this->templating           = $this->createMock(Environment::class);
        $this->router               = $this->createMock(RouterInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->pool                 = PoolMockFactory::create();
        $this->templateRegistry     = $this->createMock(TemplateRegistryInterface::class);
        $this->formFactory          = $this->createMock(FormFactoryInterface::class);
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
        $result = $action($request);

        static::assertInstanceOf(RedirectResponse::class, $result);
        static::assertSame('/foo', $result->getTargetUrl());
    }

    public function testUnauthenticated(): void
    {
        $request = new Request();

        $this->authorizationChecker->expects(static::once())
            ->method('isGranted')
            ->willReturn(false)
        ;

        $this->templateRegistry
            ->method('getTemplate')
            ->with('layout')
            ->willReturn('base.html.twig')
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

        $this->formFactory->expects(static::once())
            ->method('create')
            ->willReturn($form)
        ;

        $this->router
            ->method('generate')
            ->with('nucleos_user_admin_resetting_send_email')
            ->willReturn('/foo')
        ;

        $this->templating->expects(static::once())
            ->method('render')
            ->with('@NucleosUserAdmin/Admin/Security/Resetting/request.html.twig', [
                'base_template' => 'base.html.twig',
                'admin_pool'    => $this->pool,
                'form'          => $view,
            ])
            ->willReturn('template content')
        ;

        $this->templateRegistry
            ->method('getTemplate')
            ->with('layout')
            ->willReturn('base.html.twig')
        ;

        $action = $this->getAction();
        $result = $action($request);

        static::assertSame('template content', $result->getContent());
    }

    private function getAction(): RequestAction
    {
        return new RequestAction(
            $this->templating,
            $this->router,
            $this->authorizationChecker,
            $this->pool,
            $this->templateRegistry,
            $this->formFactory
        );
    }
}
