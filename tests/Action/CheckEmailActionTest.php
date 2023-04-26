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

use Nucleos\UserAdminBundle\Action\CheckEmailAction;
use Nucleos\UserAdminBundle\Tests\Fixtures\PoolMockFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class CheckEmailActionTest extends TestCase
{
    /**
     * @var Environment&MockObject
     */
    protected $templating;

    /**
     * @var MockObject&UrlGeneratorInterface
     */
    protected $urlGenerator;

    protected Pool $pool;

    /**
     * @var MockObject&TemplateRegistryInterface
     */
    protected $templateRegistry;

    protected int $resetTtl;

    protected function setUp(): void
    {
        $this->templating           = $this->createMock(Environment::class);
        $this->urlGenerator         = $this->createMock(UrlGeneratorInterface::class);
        $this->pool                 = PoolMockFactory::create();
        $this->templateRegistry     = $this->createMock(TemplateRegistryInterface::class);
        $this->resetTtl             = 60;
    }

    public function testWithoutUsername(): void
    {
        $request = new Request();

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('nucleos_user_admin_resetting_request')
            ->willReturn('/foo')
        ;

        $action = $this->getAction();
        $result = $action($request);

        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertSame('/foo', $result->getTargetUrl());
    }

    public function testWithUsername(): void
    {
        $request = new Request(['username' => 'bar']);

        $parameters = [
            'base_template' => 'base.html.twig',
            'admin_pool'    => $this->pool,
            'tokenLifetime' => 1,
        ];

        $this->templating->expects(self::once())
            ->method('render')
            ->with('@NucleosUserAdmin/Admin/Security/Resetting/checkEmail.html.twig', $parameters)
            ->willReturn('template content')
        ;

        $this->templateRegistry
            ->method('getTemplate')
            ->with('layout')
            ->willReturn('base.html.twig')
        ;

        $action = $this->getAction();
        $result = $action($request);

        self::assertSame('template content', $result->getContent());
    }

    private function getAction(): CheckEmailAction
    {
        return new CheckEmailAction(
            $this->templating,
            $this->urlGenerator,
            $this->pool,
            $this->templateRegistry,
            $this->resetTtl
        );
    }
}
