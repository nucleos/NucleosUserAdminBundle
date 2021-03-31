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

namespace Nucleos\UserAdminBundle\Tests\Form\Type;

use Nucleos\UserAdminBundle\Form\Type\RolesMatrixType;
use Nucleos\UserAdminBundle\Security\RolesBuilder\ExpandableRolesBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RolesMatrixTypeTest extends TypeTestCase
{
    /**
     * @var ExpandableRolesBuilderInterface&MockObject
     */
    private $roleBuilder;

    public function testGetDefaultOptions(): void
    {
        $type = new RolesMatrixType($this->roleBuilder);

        $optionResolver = new OptionsResolver();
        $type->configureOptions($optionResolver);

        $options = $optionResolver->resolve();
        static::assertCount(3, $options['choices']);
    }

    public function testGetParent(): void
    {
        $type = new RolesMatrixType($this->roleBuilder);

        static::assertSame(ChoiceType::class, $type->getParent());
    }

    public function testSubmitValidData(): void
    {
        $form = $this->factory->create(
            RolesMatrixType::class,
            null,
            [
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]
        );

        $form->submit([0 => 'ROLE_FOO']);

        static::assertTrue($form->isSynchronized());
        static::assertCount(1, $form->getData());
        static::assertContains('ROLE_FOO', $form->getData());
    }

    /**
     * @return PreloadedExtension[]
     */
    protected function getExtensions(): array
    {
        $this->roleBuilder = $this->createMock(ExpandableRolesBuilderInterface::class);

        $this->roleBuilder->method('getRoles')->willReturn(
            [
                'ROLE_FOO'   => 'ROLE_FOO',
                'ROLE_USER'  => 'ROLE_USER',
                'ROLE_ADMIN' => 'ROLE_ADMIN: ROLE_USER',
            ]
        );

        $childType = new RolesMatrixType($this->roleBuilder);

        return [
            new PreloadedExtension(
                [
                    $childType,
                ],
                []
            ),
        ];
    }
}
