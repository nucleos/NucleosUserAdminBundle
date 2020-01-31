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

namespace Nucleos\UserAdminBundle\DependencyInjection;

use Nucleos\UserAdminBundle\Admin\Entity\GroupAdmin;
use Nucleos\UserAdminBundle\Admin\Entity\UserAdmin;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nucleos_user_admin');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('security_acl')->defaultFalse()->end()

                ->arrayNode('impersonating')
                    ->children()
                        ->scalarNode('route')->defaultNull()->end()
                        ->arrayNode('parameters')
                            ->useAttributeAsKey('id')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('admin')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('group')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->defaultValue(GroupAdmin::class)->end()
                                ->scalarNode('controller')->cannotBeEmpty()->defaultValue(CRUDController::class)->end()
                                ->scalarNode('translation')->cannotBeEmpty()->defaultValue('NucleosUserAdminBundle')->end()
                            ->end()
                        ->end()
                        ->arrayNode('user')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->defaultValue(UserAdmin::class)->end()
                                ->scalarNode('controller')->cannotBeEmpty()->defaultValue(CRUDController::class)->end()
                                ->scalarNode('translation')->cannotBeEmpty()->defaultValue('NucleosUserAdminBundle')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('profile')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_avatar')->defaultValue('bundles/nucleosuseradmin/default_avatar.png')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
