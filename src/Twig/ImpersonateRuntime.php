<?php

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserAdminBundle\Twig;

use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class ImpersonateRuntime implements RuntimeExtensionInterface
{
    private RouterInterface $router;

    private ?string $route;

    /**
     * @var array<string, mixed>
     */
    private array $routeParams;

    /**
     * @param array<string, mixed> $routeParams
     */
    public function __construct(RouterInterface $router, ?string $route, array $routeParams)
    {
        $this->router      = $router;
        $this->route       = $route;
        $this->routeParams = $routeParams;
    }

    public function switchRoute(string $username): string
    {
        if (null === $this->route) {
            return '#';
        }

        return $this->router->generate($this->route, array_merge($this->routeParams, ['_switch_user' => $username]));
    }

    public function exitRoute(): string
    {
        return $this->switchRoute('_exit');
    }
}
