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

use DomainException;
use Nucleos\UserAdminBundle\Form\Type\RolesMatrixType;
use Nucleos\UserBundle\Model\UserInterface;
use Nucleos\UserBundle\Model\UserManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

abstract class UserAdmin extends AbstractAdmin
{
    /**
     * @var UserManagerInterface
     */
    protected $userManager;

    public function __construct($code, $class, $baseControllerName, UserManagerInterface $userManager)
    {
        parent::__construct($code, $class, $baseControllerName);

        $this->userManager = $userManager;
    }

    public function getNewInstance()
    {
        $instance = $this->userManager->createUser();

        // TODO: Find a better way to create editable form models
        // BC layer
        try {
            $instance->getUsername();
        } catch (DomainException $exception) {
            $instance->setUsername('');
        }

        try {
            $instance->getEmail();
        } catch (DomainException $exception) {
            $instance->setEmail('');
        }

        return $instance;
    }

    public function getFormBuilder(): FormBuilderInterface
    {
        $this->formOptions['data_class'] = $this->getClass();

        $options                      = $this->formOptions;
        $options['validation_groups'] = $this->isNewInstance() ? 'Registration' : 'Profile';

        $formBuilder = $this->getFormContractor()->getFormBuilder($this->getUniqid(), $options);

        $this->defineFormBuilder($formBuilder);

        return $formBuilder;
    }

    /**
     * @return mixed[]
     */
    public function getExportFields(): array
    {
        // avoid security field to be exported
        return array_filter(
            parent::getExportFields(),
            static function ($v): bool {
                return !\in_array($v, ['password', 'salt'], true);
            }
        );
    }

    public function preUpdate($user): void
    {
        if (!$user instanceof UserInterface) {
            return;
        }

        $this->userManager->updateCanonicalFields($user);
        $this->userManager->updatePassword($user);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('username')
            ->add('email')
            ->add('groups')
            ->add('enabled', null, ['editable' => true])
        ;

        if ($this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            $listMapper
                ->add('impersonating', 'string', [
                    'template' => '@NucleosUserAdmin/Admin/Field/impersonating.html.twig',
                ])
            ;
        }
    }

    protected function configureDatagridFilters(DatagridMapper $filterMapper): void
    {
        $filterMapper
            ->add('id')
            ->add('username')
            ->add('email')
            ->add('groups')
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->with('General')
                ->add('username')
                ->add('email')
            ->end()
            ->with('Groups')
                ->add('groups')
            ->end()
        ;
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->tab('User')
                ->with('General', ['class' => 'col-md-6'])->end()
            ->end()

            ->tab('Security')
                ->with('Status', ['class' => 'col-md-4'])->end()
                ->with('Groups', ['class' => 'col-md-4'])->end()
                ->with('Roles', ['class' => 'col-md-12'])->end()
            ->end()
        ;

        $formMapper
            ->tab('User')
                ->with('General')
                    ->add('username')
                    ->add('email')
                    ->add('plainPassword', TextType::class, [
                        'required' => $this->isNewInstance(),
                    ])
                ->end()
            ->end()

            ->tab('Security')
                ->with('Status')
                    ->add('enabled', null, ['required' => false])
                ->end()
                ->with('Groups')
                    ->add('groups', ModelType::class, [
                        'required' => false,
                        'expanded' => true,
                        'multiple' => true,
                    ])
                ->end()
                ->with('Roles')
                    ->add('roles', RolesMatrixType::class, [
                        'label'    => 'form.label_roles',
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
        return !$this->hasSubject() || null === $this->getSubject()|| null === $this->id($this->getSubject());
    }
}
