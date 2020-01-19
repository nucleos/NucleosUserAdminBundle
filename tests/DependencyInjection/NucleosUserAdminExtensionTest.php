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

    public function testLoadDefault(): void
    {
        $this->load();

        $this->assertContainerBuilderHasAlias(
            'nucleos_user_admin.user_manager',
            'nucleos_user_admin.orm.user_manager'
        );

        $this->assertContainerBuilderHasAlias(
            'nucleos_user_admin.group_manager',
            'nucleos_user_admin.orm.group_manager'
        );
    }

    public function testTwigConfigParameterIsSetting(): void
    {
        $fakeContainer = $this->getMockBuilder(ContainerBuilder::class)
            ->setMethods(['hasExtension', 'prependExtensionConfig'])
            ->getMock()
        ;

        $fakeContainer->expects(static::once())
            ->method('hasExtension')
            ->with(static::equalTo('twig'))
            ->willReturn(true)
        ;

        $fakeContainer->expects(static::once())
            ->method('prependExtensionConfig')
            ->with('twig', ['form_themes' => ['@NucleosUserAdmin/Form/form_admin_fields.html.twig']])
        ;

        foreach ($this->getContainerExtensions() as $extension) {
            $extension->prepend($fakeContainer);
        }
    }

    public function testTwigConfigParameterIsSet(): void
    {
        $fakeTwigExtension = $this->getMockBuilder(TwigExtension::class)
            ->setMethods(['load', 'getAlias'])
            ->getMock()
        ;

        $fakeTwigExtension
            ->method('getAlias')
            ->willReturn('twig')
        ;

        $this->container->registerExtension($fakeTwigExtension);

        $this->load();

        $twigConfigurations = $this->container->getExtensionConfig('twig');

        static::assertArrayHasKey(0, $twigConfigurations);
        static::assertArrayHasKey('form_themes', $twigConfigurations[0]);
        static::assertSame(
            ['@NucleosUserAdmin/Form/form_admin_fields.html.twig'],
            $twigConfigurations[0]['form_themes']
        );
    }

    public function testTwigConfigParameterIsNotSet(): void
    {
        $this->load();

        $twigConfigurations = $this->container->getExtensionConfig('twig');

        static::assertArrayNotHasKey(0, $twigConfigurations);
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
}
