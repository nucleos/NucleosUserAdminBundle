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
use Nucleos\UserBundle\Model\GroupManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * @phpstan-extends AbstractAdmin<\Nucleos\UserBundle\Model\GroupInterface>
 */
abstract class GroupAdmin extends AbstractAdmin
{
    /**
     * @var string[]
     */
    protected $formOptions = [
        'validation_groups' => 'Registration',
    ];

    /**
     * @var GroupManagerInterface
     */
    private $groupManager;

    /**
     * @param string $code
     * @param string $class
     * @param string $baseControllerName
     *
     * @phpstan-param class-string<\Nucleos\UserBundle\Model\GroupInterface> $class
     */
    public function __construct($code, $class, $baseControllerName, GroupManagerInterface $groupManager)
    {
        parent::__construct($code, $class, $baseControllerName);

        $this->groupManager = $groupManager;
    }

    protected function createNewInstance(): object
    {
        return $this->groupManager->createGroup('');
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
            ->tab('form.tab_group')
                ->with('form.group_general', ['class' => 'col-md-6'])
                    ->add('name')
                ->end()
            ->end()

            ->tab('form.tab_security')
                ->with('form.group_roles', ['class' => 'col-md-12'])
                    ->add('roles', RolesMatrixType::class, [
                        'expanded' => true,
                        'multiple' => true,
                        'required' => false,
                    ])
                ->end()
            ->end()
        ;
    }
}
