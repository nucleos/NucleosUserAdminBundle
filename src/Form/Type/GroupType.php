<?php

/*
 * This file is part of the NucleosUserAdminBundle package.
 *
 * (c) Christian Gripp <mail@core23.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleos\UserAdminBundle\Form\Type;

use Nucleos\UserBundle\Model\GroupInterface;
use Nucleos\UserBundle\Model\GroupManager;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GroupType extends AbstractType
{
    /**
     * @var GroupManager<GroupInterface>
     */
    private readonly GroupManager $groupManager;

    /**
     * @var AdminInterface<GroupInterface>
     */
    private readonly AdminInterface $groupAdmin;

    /**
     * @param GroupManager<GroupInterface>   $groupManager
     * @param AdminInterface<GroupInterface> $groupAdmin
     */
    public function __construct(
        GroupManager $groupManager,
        AdminInterface $groupAdmin,
    ) {
        $this->groupManager = $groupManager;
        $this->groupAdmin   = $groupAdmin;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'status'   => null,
                'class'    => $this->groupManager->getClass(),
                'multiple' => true,
                'expanded' => true,
                'disabled' => !$this->isMaster(),
            ])
        ;
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    private function isMaster(): bool
    {
        return $this->groupAdmin->isGranted('EDIT');
    }
}
