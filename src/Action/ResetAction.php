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

use DateTime;
use Nucleos\UserBundle\Form\Model\Resetting;
use Nucleos\UserBundle\Form\Type\ResettingFormType;
use Nucleos\UserBundle\Model\UserInterface;
use Nucleos\UserBundle\Model\UserManager;
use Nucleos\UserBundle\Security\LoginManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class ResetAction
{
    private LoggerInterface $logger;

    private Environment $twig;

    private RouterInterface $router;

    private AuthorizationCheckerInterface $authorizationChecker;

    private Pool $adminPool;

    private TemplateRegistryInterface $templateRegistry;

    private FormFactoryInterface $formFactory;

    private UserManager $userManager;

    private LoginManager $loginManager;

    private TranslatorInterface $translator;

    private Session $session;

    private int $resetTtl;

    private string $firewallName;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Environment $twig,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker,
        Pool $adminPool,
        TemplateRegistryInterface $templateRegistry,
        FormFactoryInterface $formFactory,
        UserManager $userManager,
        LoginManager $loginManager,
        TranslatorInterface $translator,
        Session $session,
        int $resetTtl,
        string $firewallName
    ) {
        $this->twig                 = $twig;
        $this->router               = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->adminPool            = $adminPool;
        $this->templateRegistry     = $templateRegistry;
        $this->formFactory          = $formFactory;
        $this->userManager          = $userManager;
        $this->loginManager         = $loginManager;
        $this->translator           = $translator;
        $this->session              = $session;
        $this->resetTtl             = $resetTtl;
        $this->firewallName         = $firewallName;
        $this->logger               = new NullLogger();
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function __invoke(Request $request, string $token): Response
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->router->generate('sonata_admin_dashboard'));
        }

        $user = $this->userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(
                sprintf('The user with "confirmation token" does not exist for value "%s"', $token)
            );
        }

        if (!$user->isPasswordRequestNonExpired($this->resetTtl)) {
            return new RedirectResponse($this->router->generate('nucleos_user_admin_resetting_request'));
        }

        $form = $this->formFactory->create(ResettingFormType::class, new Resetting($user), [
            'action'            => $this->router->generate('nucleos_user_admin_security_check', [
                'token' => $token,
            ]),
            'validation_groups' => ['ResetPassword', 'Default'],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->session->getFlashBag()->add(
                'success',
                $this->translator->trans('resetting.flash.success', [], 'NucleosUserBundle')
            );

            $response = new RedirectResponse($this->router->generate('sonata_admin_dashboard'));

            $this->resetUser($user, $response);

            return $response;
        }

        return new Response($this->twig->render('@NucleosUserAdmin/Admin/Security/Resetting/reset.html.twig', [
            'token'         => $token,
            'form'          => $form->createView(),
            'base_template' => $this->templateRegistry->getTemplate('layout'),
            'admin_pool'    => $this->adminPool,
        ]));
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function resetUser(UserInterface $user, RedirectResponse $response): void
    {
        try {
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $user->setEnabled(true);

            $this->loginManager->logInUser($this->firewallName, $user, $response);

            $user->setLastLogin(new DateTime());

            $this->userManager->updateUser($user);
        } catch (AccountStatusException $ex) {
            // We simply do not authenticate users which do not pass the user
            // checker (not enabled, expired, etc.).
            $this->logger->warning(
                sprintf('Unable to login user %d after password reset', $user->getUserIdentifier()),
                ['exception' => $ex]
            );
        }
    }
}
