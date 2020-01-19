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

namespace Nucleos\UserAdminBundle\Form\Type;

use Nucleos\UserAdminBundle\Security\RolesBuilder\ExpandableRolesBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RolesMatrixType extends AbstractType
{
    /**
     * @var ExpandableRolesBuilderInterface
     */
    private $rolesBuilder;

    public function __construct(ExpandableRolesBuilderInterface $rolesBuilder)
    {
        $this->rolesBuilder = $rolesBuilder;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'expanded'                  => true,

                'choices'                   => function (Options $options, $parentChoices): array {
                    if (false === $parentChoices) {
                        return [];
                    }

                    $roles = $this->rolesBuilder->getRoles($options['choice_translation_domain']);
                    $roles = array_keys($roles);
                    $roles = array_combine($roles, $roles);

                    return false === $roles ? [] : $roles;
                },

                'choice_translation_domain' => static function (Options $options, $value): ?string {
                    // if choice_translation_domain is true, then it's the same as translation_domain
                    if (true === $value) {
                        $value = $options['translation_domain'];
                    }

                    if (null === $value) {
                        // no translation domain yet, try to ask sonata admin
                        $admin = null;
                        if (isset($options['sonata_admin'])) {
                            $admin = $options['sonata_admin'];
                        }
                        if (null === $admin && isset($options['sonata_field_description'])) {
                            $admin = $options['sonata_field_description']->getAdmin();
                        }
                        if (null !== $admin) {
                            $value = $admin->getTranslationDomain();
                        }
                    }

                    return $value;
                },

                'data_class' => null,
            ]
        );
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'user_roles_matrix';
    }
}
