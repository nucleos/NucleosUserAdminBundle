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

use Nucleos\UserAdminBundle\Controller\UserCRUDController;
use Nucleos\UserAdminBundle\Twig\AvatarRuntime;
use Nucleos\UserAdminBundle\Twig\ImpersonateRuntime;
use RuntimeException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class NucleosUserAdminExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('twig')) {
            // add custom form widgets
            $container->prependExtensionConfig(
                'twig',
                ['form_themes' => ['@NucleosUserAdmin/Form/form_admin_fields.html.twig']]
            );
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $config        = $processor->processConfiguration($configuration, $configs);
        $config        = $this->fixImpersonating($config);

        $config['manager_type'] = $container->getParameter('nucleos_user.storage');

        /** @var array<string, mixed> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (isset($bundles['SonataAdminBundle'])) {
            $loader->load('admin.php');

            if (\in_array($config['manager_type'], ['orm', 'mongodb'], true)) {
                $loader->load(sprintf('admin_%s.php', $config['manager_type']));
            }
        }

        $loader->load('twig.php');
        $loader->load('action.php');
        $loader->load('avatar.php');

        if ($config['security_acl']) {
            $loader->load('security_acl.php');
        }

        $this->configureAvatar($config, $container);
        $this->configureAdminClass($config, $container);
        $this->configureTranslationDomain($config, $container);
        $this->configureController($config, $container);

        $loader->load('impersonating.php');

        if (false !== $config['impersonating']) {
            $container->getDefinition(ImpersonateRuntime::class)
                ->replaceArgument(1, $config['impersonating']['route'])
                ->replaceArgument(2, $config['impersonating']['parameters'])
            ;
        }

        $this->fixControllerConfig($container);
    }

    private function configureAvatar(array $config, ContainerBuilder $container): void
    {
        $container->getDefinition(AvatarRuntime::class)
            ->replaceArgument(1, $config['avatar']['resolver'])
        ;
        $container->setParameter('nucleos_user_admin.default_avatar', $config['avatar']['default_avatar']);
    }

    /**
     * @throws RuntimeException
     */
    private function fixImpersonating(array $config): array
    {
        if (!isset($config['impersonating']['parameters'])) {
            $config['impersonating']['parameters'] = [];
        }

        if (!isset($config['impersonating']['route'])) {
            $config['impersonating'] = false;
        }

        return $config;
    }

    private function configureAdminClass(array $config, ContainerBuilder $container): void
    {
        $container->setParameter('nucleos_user_admin.admin.user.class', $config['admin']['user']['class']);
        $container->setParameter('nucleos_user_admin.admin.group.class', $config['admin']['group']['class']);
    }

    private function configureTranslationDomain(array $config, ContainerBuilder $container): void
    {
        $container->setParameter(
            'nucleos_user_admin.admin.user.translation_domain',
            $config['admin']['user']['translation']
        );
        $container->setParameter(
            'nucleos_user_admin.admin.group.translation_domain',
            $config['admin']['group']['translation']
        );
    }

    private function configureController(array $config, ContainerBuilder $container): void
    {
        $container->setParameter('nucleos_user_admin.admin.user.controller', $config['admin']['user']['controller']);
        $container->setParameter('nucleos_user_admin.admin.group.controller', $config['admin']['group']['controller']);
    }

    private function fixControllerConfig(ContainerBuilder $container): void
    {
        if (!is_subclass_of(UserCRUDController::class, ServiceSubscriberInterface::class)) {
            return;
        }

        $container
            ->getDefinition('nucleos_user_admin.controller.user')
            ->addTag('container.service_subscriber')
        ;
    }
}
