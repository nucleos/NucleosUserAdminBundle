<?php

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserAdminBundle\Controller;

use Nucleos\UserBundle\Event\AccountDeletionEvent;
use Nucleos\UserBundle\Model\UserInterface;
use Nucleos\UserBundle\NucleosUserEvents;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @phpstan-extends CRUDController<UserInterface>
 */
class UserCRUDController extends CRUDController
{
    private ?EventDispatcherInterface $eventDispatcher;

    public function __construct(?EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function preDelete(Request $request, $object): ?Response
    {
        if (null === $this->eventDispatcher) {
            return null;
        }

        $this->eventDispatcher->dispatch(new AccountDeletionEvent($object, $request), NucleosUserEvents::ACCOUNT_DELETION);

        return null;
    }
}
