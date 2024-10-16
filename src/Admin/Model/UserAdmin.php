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
use Nucleos\UserAdminBundle\Form\Type\GroupType;
use Nucleos\UserAdminBundle\Form\Type\RolesMatrixType;
use Nucleos\UserBundle\Model\LocaleAwareUser;
use Nucleos\UserBundle\Model\UserInterface;
use Nucleos\UserBundle\Model\UserManager;
use Nucleos\UserBundle\Util\UserManipulator;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;

/**
 * @phpstan-extends AbstractAdmin<UserInterface>
 */
abstract class UserAdmin extends AbstractAdmin
{
    protected UserManager $userManager;

    private ?UserManipulator $userManipulator;

    public function __construct(UserManager $userManager, ?UserManipulator $userManipulator = null)
    {
        parent::__construct();

        $this->userManager     = $userManager;
        $this->userManipulator = $userManipulator;
    }

    public function preUpdate($object): void
    {
        if (method_exists($this->userManager, 'updateCanonicalFields')) {
            $this->userManager->updateCanonicalFields($object);
        }
    }

    protected function postUpdate(object $object): void
    {
        $this->updatePassword($object);
    }

    protected function configureFormOptions(array &$formOptions): void
    {
        $formOptions['validation_groups'] = ['User', $this->isNewInstance() ? 'Registration' : 'Profile'];
    }

    protected function configureExportFields(): array
    {
        // avoid security field to be exported
        return array_filter(
            parent::configureExportFields(),
            static function ($v): bool {
                return !\in_array($v, ['password', 'salt'], true);
            }
        );
    }

    protected function createNewInstance(): object
    {
        $instance = $this->userManager->createUser();

        // TODO: Find a better way to create editabe form models
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

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('username')
            ->add('email')
            ->add('groups')
            ->add('enabled', null, ['editabe' => true])
        ;

        if ($this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            $list
                ->add('id', 'string', [
                    'template' => '@NucleosUserAdmin/Admin/Field/impersonating.html.twig',
                ])
            ;
        }
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('username')
            ->add('email')
            ->add('groups')
        ;
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->with('general', ['label' => 'show.group_general'])
                ->add('username')
                ->add('email')
                ->add('enabled', FieldDescriptionInterface::TYPE_BOOLEAN)
            ->end()
            ->with('groups', ['label' => 'show.group_groups'])
                ->add('groups')
            ->end()
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->tab('user', ['label' => 'form.group_user'])
                ->with('general', ['class' => 'col-md-6'])->end()
                ->ifTrue($this->isLocaleAwareSubject())
                    ->with('locale', ['class' => 'col-md-6'])->end()
                ->ifEnd()
            ->end()

            ->tab('security', ['label' => 'form.group_security'])
                ->with('groups', ['class' => 'col-md-8', 'label' => 'form.group_groups'])->end()
                ->with('status', ['class' => 'col-md-4', 'label' => 'form.group_status'])->end()
                ->with('roles', ['class' => 'col-md-12', 'label' => 'form.group_roles'])->end()
            ->end()
        ;

        $form
            ->tab('user')
                ->with('general')
                    ->add('username')
                    ->add('email')
                    ->add('plainPassword', TextType::class, [
                        'required' => $this->isNewInstance(),
                    ])
                ->end()
                ->ifTrue($this->isLocaleAwareSubject())
                    ->with('locale')
                        ->add('locale', LocaleType::class, [
                            'required' => false,
                        ])
                        ->add('timezone', TimezoneType::class, [
                            'required'  => false,
                        ])
                    ->end()
                ->ifEnd()
            ->end()

            ->tab('security')
                ->with('status')
                    ->add('enabled', null, ['required' => false])
                ->end()
                ->with('groups')
                    ->add('groups', GroupType::class, [
                        'required' => false,
                        'expanded' => true,
                        'multiple' => true,
                    ])
                ->end()
                ->with('roles')
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

    private function updatePassword(UserInterface $user): void
    {
        $plainPassword = $user->getPlainPassword();

        if (null === $plainPassword) {
            return;
        }

        $this->userManipulator?->changePassword($user->getUsername(), $plainPassword);
    }

    private function isNewInstance(): bool
    {
        return !$this->hasSubject() || null === $this->id($this->getSubject());
    }

    private function isLocaleAwareSubject(): bool
    {
        return is_subclass_of($this->getClass(), LocaleAwareUser::class);
    }
}
