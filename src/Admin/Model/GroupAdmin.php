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

namespace Nucleos\UserAdminBundle\Admin\Model;

use Nucleos\UserAdminBundle\Form\Type\RolesMatrixType;
use Nucleos\UserBundle\Model\GroupInterface;
use Nucleos\UserBundle\Model\GroupManager;
use RuntimeException;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * @phpstan-extends AbstractAdmin<GroupInterface>
 */
abstract class GroupAdmin extends AbstractAdmin
{
    private GroupManager $groupManager;

    /**
     * @phpstan-param GroupManager|string $codeOrGroupManager
     * @phpstan-param class-string<GroupInterface> $class
     *
     * @param mixed $codeOrGroupManager
     */
    public function __construct($codeOrGroupManager, string $class = null, string $baseControllerName = null, GroupManager $groupManager = null)
    {
        if ($codeOrGroupManager instanceof GroupManager) {
            $this->groupManager = $codeOrGroupManager;
        } else {
            parent::__construct($codeOrGroupManager, $class, $baseControllerName);

            if (null === $groupManager) {
                throw new RuntimeException('Cannot create admin. GroupManager cannot be null');
            }

            $this->groupManager = $groupManager;
        }
    }

    protected function createNewInstance(): object
    {
        return $this->groupManager->createGroup('');
    }

    protected function configureFormOptions(array &$formOptions): void
    {
        $formOptions['validation_groups'] = $this->isNewInstance() ? 'Registration' : 'Profile';
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('roles')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
        ;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->tab('group', ['label' => 'form.group_groups'])
                ->with('general', ['class' => 'col-md-6', 'label' => 'focl'])
                    ->add('name')
                ->end()
            ->end()

            ->tab('security', ['label' => 'form.group_security'])
                ->with('roles', ['class' => 'col-md-12', 'label' => 'form.group_roles'])
                    ->add('roles', RolesMatrixType::class, [
                        'expanded' => true,
                        'multiple' => true,
                        'required' => false,
                    ])
                ->end()
            ->end()
        ;
    }

    private function isNewInstance(): bool
    {
        return !$this->hasSubject() || null === $this->id($this->getSubject());
    }
}
