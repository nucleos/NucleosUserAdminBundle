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
use Nucleos\UserBundle\Model\UserInterface;
use Nucleos\UserBundle\NucleosUserEvents;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

final class LoginAction
{
    private Environment $twig;

    private EventDispatcherInterface $eventDispatcher;

    private UrlGeneratorInterface $urlGenerator;

    private AuthorizationCheckerInterface $authorizationChecker;

    private Pool $adminPool;

    private TemplateRegistryInterface $templateRegistry;

    private TokenStorageInterface $tokenStorage;

    private Session $session;

    private ?CsrfTokenManagerInterface $csrfTokenManager = null;

    public function __construct(
        Environment $twig,
        EventDispatcherInterface $eventDispatcher,
        UrlGeneratorInterface $urlGenerator,
        AuthorizationCheckerInterface $authorizationChecker,
        Pool $adminPool,
        TemplateRegistryInterface $templateRegistry,
        TokenStorageInterface $tokenStorage,
        Session $session
    ) {
        $this->twig                 = $twig;
        $this->eventDispatcher      = $eventDispatcher;
        $this->urlGenerator         = $urlGenerator;
        $this->authorizationChecker = $authorizationChecker;
        $this->adminPool            = $adminPool;
        $this->templateRegistry     = $templateRegistry;
        $this->tokenStorage         = $tokenStorage;
        $this->session              = $session;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function __invoke(Request $request): Response
    {
        if ($this->isAuthenticated()) {
            $this->session->getFlashBag()->add('nucleos_user_admin_error', 'nucleos_user_admin_already_authenticated');

            return new RedirectResponse($this->urlGenerator->generate('sonata_admin_dashboard'));
        }

        $event = new GetResponseLoginEvent($request);
        $this->eventDispatcher->dispatch($event, NucleosUserEvents::SECURITY_LOGIN_INITIALIZE);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $session = $request->hasSession() ? $request->getSession() : null;

        $authErrorKey    = Security::AUTHENTICATION_ERROR;
        $lastUsernameKey = Security::LAST_USERNAME;

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

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $refererUri = $request->server->get('HTTP_REFERER', '');
            $url        = '' !== $refererUri && $refererUri !== $request->getUri() ? $refererUri : $this->urlGenerator->generate('sonata_admin_dashboard');

            return new RedirectResponse($url);
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get($lastUsernameKey);

        return new Response($this->twig->render('@NucleosUserAdmin/Admin/Security/login.html.twig', [
            'admin_pool'    => $this->adminPool,
            'base_template' => $this->templateRegistry->getTemplate('layout'),
            'csrf_token'    => $this->getCsrfToken(),
            'error'         => $error,
            'last_username' => $lastUsername,
            'reset_route'   => $this->urlGenerator->generate('nucleos_user_admin_resetting_request'),
        ]));
    }

    public function setCsrfTokenManager(CsrfTokenManagerInterface $csrfTokenManager): void
    {
        $this->csrfTokenManager = $csrfTokenManager;
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
}
