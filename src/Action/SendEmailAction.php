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
use Nucleos\UserBundle\Mailer\ResettingMailer;
use Nucleos\UserBundle\Model\UserInterface;
use Nucleos\UserBundle\Model\UserManager;
use Nucleos\UserBundle\Util\TokenGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class SendEmailAction
{
    private UrlGeneratorInterface $urlGenerator;

    private UserManager $userManager;

    private ResettingMailer $mailer;

    private TokenGenerator $tokenGenerator;

    /**
     * @var UserProviderInterface<UserInterface>
     */
    private UserProviderInterface $userProvider;

    private int $resetTtl;

    /**
     * @param UserProviderInterface<UserInterface> $userProvider
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        UserManager $userManager,
        ResettingMailer $resettingMailer,
        TokenGenerator $tokenGenerator,
        UserProviderInterface $userProvider,
        int $resetTtl
    ) {
        $this->urlGenerator     = $urlGenerator;
        $this->userManager      = $userManager;
        $this->mailer           = $resettingMailer;
        $this->tokenGenerator   = $tokenGenerator;
        $this->resetTtl         = $resetTtl;
        $this->userProvider     = $userProvider;
    }

    public function __invoke(Request $request): Response
    {
        $username = (string) $request->request->get('username', '');

        $user = null;

        try {
            $user = '' === $username ? null : $this->userProvider->loadUserByIdentifier($username);
        } catch (UserNotFoundException) {
        }

        if ($user instanceof UserInterface && !$user->isPasswordRequestNonExpired($this->resetTtl)) {
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
