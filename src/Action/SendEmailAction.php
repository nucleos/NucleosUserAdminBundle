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
use Nucleos\UserBundle\Mailer\MailerInterface;
use Nucleos\UserBundle\Model\UserManagerInterface;
use Nucleos\UserBundle\Util\TokenGeneratorInterface;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class SendEmailAction
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var Pool
     */
    private $adminPool;

    /**
     * @var TemplateRegistryInterface
     */
    private $templateRegistry;

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var TokenGeneratorInterface
     */
    private $tokenGenerator;

    /**
     * @var int
     */
    private $resetTtl;

    public function __construct(
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
        Pool $adminPool,
        TemplateRegistryInterface $templateRegistry,
        UserManagerInterface $userManager,
        MailerInterface $mailer,
        TokenGeneratorInterface $tokenGenerator,
        int $resetTtl
    ) {
        $this->twig             = $twig;
        $this->urlGenerator     = $urlGenerator;
        $this->adminPool        = $adminPool;
        $this->templateRegistry = $templateRegistry;
        $this->userManager      = $userManager;
        $this->mailer           = $mailer;
        $this->tokenGenerator   = $tokenGenerator;
        $this->resetTtl         = $resetTtl;
    }

    public function __invoke(Request $request): Response
    {
        $username = $request->request->get('username');

        $user = null === $username ? null : $this->userManager->findUserByUsernameOrEmail($username);

        if (null !== $user && !$user->isPasswordRequestNonExpired($this->resetTtl)) {
            if (!$user->isAccountNonLocked()) {
                return new RedirectResponse(
                    $this->urlGenerator->generate('nucleos_user_admin_resetting_request')
                );
            }

            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken($this->tokenGenerator->generateToken());
            }

            $this->mailer->sendResettingEmailMessage($user);
            $user->setPasswordRequestedAt(new DateTime());
            $this->userManager->updateUser($user);
        }

        return new RedirectResponse($this->urlGenerator->generate('nucleos_user_admin_resetting_check_email', [
            'username' => $username,
        ]));
    }
}
