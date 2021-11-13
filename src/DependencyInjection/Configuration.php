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
use Nucleos\UserAdminBundle\Avatar\StaticAvatarResolver;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nucleos_user_admin');

        $rootNode = $treeBuilder->getRootNode();

        $this->addSecuritySection($rootNode);
        $this->addImpersonatingSection($rootNode);
        $this->addAdminSection($rootNode);
        $this->addAvatarSection($rootNode);

        return $treeBuilder;
    }

    private function addSecuritySection(NodeDefinition $node): void
    {
        $node
            ->children()
                ->booleanNode('security_acl')->defaultFalse()->end()

            ->end()
        ;
    }

    private function addImpersonatingSection(NodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('impersonating')
                    ->children()
                        ->scalarNode('route')->defaultNull()->end()
                        ->arrayNode('parameters')
                            ->useAttributeAsKey('id')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addAdminSection(NodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('admin')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('group')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->defaultValue(GroupAdmin::class)->end()
                                ->scalarNode('controller')->cannotBeEmpty()->defaultValue('%sonata.admin.configuration.default_controller%')->end()
                                ->scalarNode('translation')->cannotBeEmpty()->defaultValue('NucleosUserAdminBundle')->end()
                            ->end()
                        ->end()
                        ->arrayNode('user')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->defaultValue(UserAdmin::class)->end()
                                ->scalarNode('controller')->cannotBeEmpty()->defaultValue('nucleos_user_admin.controller.user')->end()
                                ->scalarNode('translation')->cannotBeEmpty()->defaultValue('NucleosUserAdminBundle')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ;
    }

    private function addAvatarSection(NodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('avatar')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('resolver')->defaultValue(StaticAvatarResolver::class)->end()
                        ->scalarNode('default_avatar')->defaultValue('/bundles/nucleosuseradmin/default_avatar.png')->end()
                    ->end()
                ->end()
            ->end()
            ;
    }
}
