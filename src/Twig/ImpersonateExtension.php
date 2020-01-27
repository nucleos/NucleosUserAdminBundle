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
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ImpersonateExtension extends AbstractExtension
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string|null
     */
    private $route;

    /**
     * @var array<string, mixed>
     */
    private $routeParams;

    /**
     * ImpersonatingExtension constructor.
     *
     * @param string               $route
     * @param array<string, mixed> $routeParams
     */
    public function __construct(RouterInterface $router, ?string $route, array $routeParams)
    {
        $this->router      = $router;
        $this->route       = $route;
        $this->routeParams = $routeParams;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('impersonate', [$this, 'switchRoute']),
            new TwigFunction('impersonateExit', [$this, 'exitRoute']),
        ];
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
