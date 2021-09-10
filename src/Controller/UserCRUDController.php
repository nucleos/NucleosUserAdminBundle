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
    protected function preDelete(Request $request, $object): ?Response
    {
        $this->getEventDispatcher()->dispatch(new AccountDeletionEvent($object, $request), NucleosUserEvents::ACCOUNT_DELETION);

        return null;
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        $eventDispatcher = $this->get('event_dispatcher');

        \assert($eventDispatcher instanceof EventDispatcherInterface);

        return $eventDispatcher;
    }
}
