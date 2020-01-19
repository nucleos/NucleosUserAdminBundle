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

final class RestoreRolesTransformer implements DataTransformerInterface
{
    /**
     * @var array
     */
    private $originalRoles;

    /**
     * @var EditableRolesBuilderInterface
     */
    private $rolesBuilder;

    public function __construct(EditableRolesBuilderInterface $rolesBuilder)
    {
        $this->rolesBuilder = $rolesBuilder;
    }

    public function setOriginalRoles(?array $originalRoles = []): void
    {
        if (null === $originalRoles) {
            $originalRoles = [];
        }

        $this->originalRoles = $originalRoles;
    }

    /**
     * @param mixed $value
     *
     * @return mixed|null
     */
    public function transform($value)
    {
        if (null === $value) {
            return $value;
        }

        if (null === $this->originalRoles) {
            throw new RuntimeException('Invalid state, originalRoles array is not set');
        }

        return $value;
    }

    /**
     * @param mixed $selectedRoles
     *
     * @return mixed[]
     */
    public function reverseTransform($selectedRoles): array
    {
        if (null === $this->originalRoles) {
            throw new RuntimeException('Invalid state, originalRoles array is not set');
        }

        $availableRoles = $this->rolesBuilder->getRoles();

        $hiddenRoles = array_diff($this->originalRoles, array_keys($availableRoles));

        return array_merge($selectedRoles, $hiddenRoles);
    }
}
