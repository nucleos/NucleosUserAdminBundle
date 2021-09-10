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

namespace Nucleos\UserAdminBundle\Action;

use Nucleos\UserBundle\Form\Type\RequestPasswordFormType;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

final class RequestAction
{
    private Environment $twig;

    private RouterInterface $router;

    private AuthorizationCheckerInterface $authorizationChecker;

    private Pool $adminPool;

    private TemplateRegistryInterface $templateRegistry;

    private FormFactoryInterface $formFactory;

    public function __construct(
        Environment $twig,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker,
        Pool $adminPool,
        TemplateRegistryInterface $templateRegistry,
        FormFactoryInterface $formFactory
    ) {
        $this->twig                 = $twig;
        $this->router               = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->adminPool            = $adminPool;
        $this->templateRegistry     = $templateRegistry;
        $this->formFactory          = $formFactory;
    }

    public function __invoke(Request $request): Response
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->router->generate('sonata_admin_dashboard'));
        }

        $form = $this->formFactory->create(RequestPasswordFormType::class, null, [
            'action' => $this->router->generate('nucleos_user_admin_resetting_send_email'),
            'method' => 'POST',
        ]);

        return new Response($this->twig->render('@NucleosUserAdmin/Admin/Security/Resetting/request.html.twig', [
            'form'          => $form->createView(),
            'base_template' => $this->templateRegistry->getTemplate('layout'),
            'admin_pool'    => $this->adminPool,
        ]));
    }
}
