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

use Nucleos\UserBundle\Event\GetResponseLoginEvent;
use Nucleos\UserBundle\Form\Type\LoginFormType;
use Nucleos\UserBundle\Model\UserInterface;
use Nucleos\UserBundle\NucleosUserEvents;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class LoginAction
{
    private Environment $twig;

    private EventDispatcherInterface $eventDispatcher;

    private RouterInterface $router;

    private AuthorizationCheckerInterface $authorizationChecker;

    private Pool $adminPool;

    private TemplateRegistryInterface $templateRegistry;

    private TokenStorageInterface $tokenStorage;

    private ?CsrfTokenManagerInterface $csrfTokenManager = null;

    private FormFactoryInterface $formFactory;

    private TranslatorInterface $translator;

    private ?AuthenticationUtils $authenticationUtils;

    public function __construct(
        Environment $twig,
        EventDispatcherInterface $eventDispatcher,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker,
        Pool $adminPool,
        TemplateRegistryInterface $templateRegistry,
        TokenStorageInterface $tokenStorage,
        FormFactoryInterface $formFactory,
        TranslatorInterface $translator,
        ?AuthenticationUtils $authenticationUtils = null
    ) {
        $this->twig                 = $twig;
        $this->eventDispatcher      = $eventDispatcher;
        $this->router               = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->adminPool            = $adminPool;
        $this->templateRegistry     = $templateRegistry;
        $this->tokenStorage         = $tokenStorage;
        $this->formFactory          = $formFactory;
        $this->translator           = $translator;
        $this->authenticationUtils  = $authenticationUtils;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function __invoke(Request $request): Response
    {
        $session = $request->hasSession() ? $request->getSession() : null;

        if ($this->isAuthenticated()) {
            $message = $this->translator->trans('nucleos_user_admin_already_authenticated', [], 'NucleosUserAdminBundle');
            $this->addFlash($session, 'sonata_flash_info', $message);

            return new RedirectResponse($this->router->generate('sonata_admin_dashboard'));
        }

        $event = new GetResponseLoginEvent($request);
        $this->eventDispatcher->dispatch($event, NucleosUserEvents::SECURITY_LOGIN_INITIALIZE);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $refererUri = $request->server->get('HTTP_REFERER', '');
            $url        = '' !== $refererUri && $refererUri !== $request->getUri() ? $refererUri : $this->router->generate('sonata_admin_dashboard');

            return new RedirectResponse($url);
        }

        $form = $this->formFactory
            ->create(LoginFormType::class, null, [
                'action' => $this->router->generate('nucleos_user_admin_security_check'),
                'method' => 'POST',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'security.login.submit',
            ])
        ;

        // last username entered by the user

        return new Response($this->twig->render('@NucleosUserAdmin/Admin/Security/login.html.twig', [
            'form'          => $form->createView(),
            'admin_pool'    => $this->adminPool,
            'base_template' => $this->templateRegistry->getTemplate('layout'),
            'csrf_token'    => $this->getCsrfToken(),
            'error'         => $this->getLastAuthenticationError($request),
            'last_username' => $this->getLastUsername($session),
            'reset_route'   => $this->router->generate('nucleos_user_admin_resetting_request'),
        ]));
    }

    public function setCsrfTokenManager(CsrfTokenManagerInterface $csrfTokenManager): void
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getLastAuthenticationError(Request $request): ?AuthenticationException
    {
        if (null !== $this->authenticationUtils) {
            return $this->authenticationUtils->getLastAuthenticationError();
        }

        $authErrorKey = Security::AUTHENTICATION_ERROR;
        $session      = $request->hasSession() ? $request->getSession() : null;

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        return $error;
    }

    private function getLastUsername(?SessionInterface $session): ?string
    {
        if (null !== $this->authenticationUtils) {
            return $this->authenticationUtils->getLastUsername();
        }

        return (null === $session) ? '' : $session->get(Security::LAST_USERNAME);
    }

    private function isAuthenticated(): bool
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return false;
        }

        $user = $token->getUser();

        return $user instanceof UserInterface;
    }

    private function getCsrfToken(): ?string
    {
        return null !== $this->csrfTokenManager ? $this->csrfTokenManager->getToken('authenticate')->getValue() : null;
    }

    private function addFlash(?SessionInterface $session, string $type, string $message): void
    {
        if (!$session instanceof Session) {
            return;
        }

        $session->getFlashBag()->add($type, $message);
    }
}
