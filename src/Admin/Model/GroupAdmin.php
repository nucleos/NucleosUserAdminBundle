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

use Nucleos\UserAdminBundle\Form\Type\SecurityRolesType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

abstract class GroupAdmin extends AbstractAdmin
{
    /**
     * @var string[]
     */
    protected $formOptions = [
        'validation_groups' => 'Registration',
    ];

    public function getNewInstance()
    {
        $class = $this->getClass();

        return new $class('', []);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('name')
            ->add('roles')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('name')
        ;
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->tab('Group')
                ->with('General', ['class' => 'col-md-6'])
                    ->add('name')
                ->end()
            ->end()

            ->tab('Security')
                ->with('Roles', ['class' => 'col-md-12'])
                ->add('roles', SecurityRolesType::class, [
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
                ])
                ->end()
            ->end()
        ;
    }
}
