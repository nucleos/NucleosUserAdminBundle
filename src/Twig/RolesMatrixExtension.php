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

namespace Nucleos\UserAdminBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RolesMatrixExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderMatrix', [RolesMatrixRuntime::class, 'renderMatrix'], ['needs_environment' => true]),
            new TwigFunction('renderRolesList', [RolesMatrixRuntime::class, 'renderRolesList'], ['needs_environment' => true]),
        ];
    }
}
