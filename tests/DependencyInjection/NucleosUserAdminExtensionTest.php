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

namespace Nucleos\UserAdminBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Nucleos\UserAdminBundle\Admin\Entity\GroupAdmin;
use Nucleos\UserAdminBundle\Admin\Entity\UserAdmin;
use Nucleos\UserAdminBundle\DependencyInjection\Configuration;
use Nucleos\UserAdminBundle\DependencyInjection\NucleosUserAdminExtension;
use Nucleos\UserAdminBundle\Twig\ImpersonateExtension;
use Nucleos\UserAdminBundle\Twig\ImpersonateRuntime;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class NucleosUserAdminExtensionTest extends AbstractExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setParameter('kernel.bundles', ['SonataAdminBundle' => true]);
        $this->setParameter('nucleos_user.storage', 'orm');
    }

    public function testTwigConfigParameterIsSetting(): void
    {
        $fakeContainer = $this->createContainerBuilder();

        $fakeContainer->expects(self::once())
            ->method('hasExtension')
            ->with(self::equalTo('twig'))
            ->willReturn(true)
        ;

        $fakeContainer->expects(self::once())
            ->method('prependExtensionConfig')
            ->with('twig', ['form_themes' => ['@NucleosUserAdmin/Form/form_admin_fields.html.twig']])
        ;

        foreach ($this->getContainerExtensions() as $extension) {
            $extension->prepend($fakeContainer);
        }
    }

    public function testTwigConfigParameterIsSet(): void
    {
        $fakeTwigExtension = $this->createTwigExtension();

        $fakeTwigExtension
            ->method('getAlias')
            ->willReturn('twig')
        ;

        $this->container->registerExtension($fakeTwigExtension);

        $this->load();

        $twigConfigurations = $this->container->getExtensionConfig('twig');

        self::assertArrayHasKey(0, $twigConfigurations);
        self::assertArrayHasKey('form_themes', $twigConfigurations[0]);
        self::assertSame(
            ['@NucleosUserAdmin/Form/form_admin_fields.html.twig'],
            $twigConfigurations[0]['form_themes']
        );
    }

    public function testTwigConfigParameterIsNotSet(): void
    {
        $this->load();

        $twigConfigurations = $this->container->getExtensionConfig('twig');

        self::assertArrayNotHasKey(0, $twigConfigurations);
    }

    public function testLoadWithoutImpersonating(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(ImpersonateExtension::class);
        $this->assertContainerBuilderHasService(ImpersonateRuntime::class);
    }

    public function testLoadWithImpersonating(): void
    {
        $this->load([
            'impersonating' => [
                'route'      => 'my_route',
                'parameters' => [
                    'foo' => 'bar',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService(ImpersonateExtension::class);
        $this->assertContainerBuilderHasService(ImpersonateRuntime::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(ImpersonateRuntime::class, 1, 'my_route');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(ImpersonateRuntime::class, 2, ['foo' => 'bar']);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCorrectAdminClass(): void
    {
        $this->load(['admin' => ['user' => [
            'class' => UserAdmin::class,
        ]]]);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCorrectModelClassWithNotDefaultManagerType(): void
    {
        $this->load(
            [
                'admin'        => [
                    'user'  => ['class' => UserAdmin::class],
                    'group' => ['class' => GroupAdmin::class],
                ],
            ]
        );
    }

    /**
     * @return mixed[]
     */
    protected function getMinimalConfiguration(): array
    {
        return (new Processor())->process((new Configuration())->getConfigTreeBuilder()->buildTree(), []);
    }

    /**
     * @return NucleosUserAdminExtension[]
     */
    protected function getContainerExtensions(): array
    {
        return [
            new NucleosUserAdminExtension(),
        ];
    }

    /**
     * @return ContainerBuilder&MockObject
     */
    private function createContainerBuilder(): ContainerBuilder
    {
        $mockBuilder = $this->getMockBuilder(ContainerBuilder::class);

        // @phpstan-ignore-next-line
        if (!method_exists(ContainerBuilder::class, 'hasExtension')) {
            $mockBuilder->addMethods(['hasExtension']);
        }
        // @phpstan-ignore-next-line
        if (!method_exists(ContainerBuilder::class, 'prependExtensionConfig')) {
            $mockBuilder->addMethods(['prependExtensionConfig']);
        }

        return $mockBuilder->getMock();
    }

    /**
     * @return MockObject&TwigExtension
     */
    private function createTwigExtension(): TwigExtension
    {
        $mockBuilder = $this->getMockBuilder(TwigExtension::class);

        // @phpstan-ignore-next-line
        if (!method_exists(TwigExtension::class, 'load')) {
            $mockBuilder = $mockBuilder->addMethods(['load']);
        }
        // @phpstan-ignore-next-line
        if (!method_exists(TwigExtension::class, 'getAlias')) {
            $mockBuilder = $mockBuilder->addMethods(['getAlias']);
        }

        return $mockBuilder->getMock();
    }
}
