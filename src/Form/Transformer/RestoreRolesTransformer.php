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

namespace Nucleos\UserAdminBundle\Form\Transformer;

use Nucleos\UserAdminBundle\Security\EditableRolesBuilderInterface;
use RuntimeException;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @phpstan-implements DataTransformerInterface<string[], string[]>
 */
final class RestoreRolesTransformer implements DataTransformerInterface
{
    /**
     * @var string[]|null
     */
    private ?array $originalRoles = null;

    private EditableRolesBuilderInterface $rolesBuilder;

    public function __construct(EditableRolesBuilderInterface $rolesBuilder)
    {
        $this->rolesBuilder = $rolesBuilder;
    }

    /**
     * @param string[]|null $originalRoles
     */
    public function setOriginalRoles(?array $originalRoles = []): void
    {
        if (null === $originalRoles) {
            $originalRoles = [];
        }

        $this->originalRoles = $originalRoles;
    }

    public function transform($value): mixed
    {
        if (null === $value) {
            return null;
        }

        if (null === $this->originalRoles) {
            throw new RuntimeException('Invalid state, originalRoles array is not set');
        }

        return $value;
    }

    public function reverseTransform($value): mixed
    {
        if (null === $value) {
            $value = [];
        }

        if (null === $this->originalRoles) {
            throw new RuntimeException('Invalid state, originalRoles array is not set');
        }

        $availableRoles = $this->rolesBuilder->getRoles();

        $hiddenRoles = array_diff($this->originalRoles, array_keys($availableRoles));

        return array_merge($value, $hiddenRoles);
    }
}
