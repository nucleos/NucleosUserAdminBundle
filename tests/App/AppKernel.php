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

namespace Nucleos\UserAdminBundle\Tests\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Nucleos\UserAdminBundle\NucleosUserAdminBundle;
use Nucleos\UserBundle\NucleosUserBundle;
use Psr\Log\NullLogger;
use Sonata\AdminBundle\SonataAdminBundle;
use Sonata\BlockBundle\SonataBlockBundle;
use Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle;
use Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle;
use Sonata\Form\Bridge\Symfony\SonataFormBundle;
use Sonata\Twig\Bridge\Symfony\SonataTwigBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

final class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct()
    {
        parent::__construct('test', false);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();

        yield new TwigBundle();

        yield new TwigExtraBundle();

        yield new SecurityBundle();

        yield new DoctrineBundle();

        yield new KnpMenuBundle();

        yield new SonataBlockBundle();

        yield new SonataAdminBundle();

        yield new SonataDoctrineORMAdminBundle();

        yield new SonataDoctrineBundle();

        yield new SonataFormBundle();

        yield new SonataTwigBundle();

        yield new NucleosUserBundle();

        yield new NucleosUserAdminBundle();
    }

    public function getCacheDir(): string
    {
        return $this->getBaseDir().'cache';
    }

    public function getLogDir(): string
    {
        return $this->getBaseDir().'log';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@SonataAdminBundle/Resources/config/routing/sonata_admin.xml')
            ->prefix('/admin')
        ;
        $routes->import('.', 'sonata_admin')
            ->prefix('/admin')
        ;
        $routes->import('@NucleosUserBundle/Resources/config/routing/security.php');

        try {
            $routes->import('@NucleosUserBundle/Resources/config/routing/update_security.php');
        } catch (LoaderLoadException) {
            $routes->import('@NucleosUserBundle/Resources/config/routing/change_password.php');
        }
        $routes->import('@NucleosUserBundle/Resources/config/routing/resetting.php')
            ->prefix('/resetting')
        ;

        $routes->import('@NucleosUserAdminBundle/Resources/config/routing/admin_security.php')
            ->prefix('/admin')
        ;
        $routes->import('@NucleosUserAdminBundle/Resources/config/routing/admin_resetting.php')
            ->prefix('/admin/resetting')
        ;
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import(__DIR__.'/config/config.php');
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->register('logger', NullLogger::class);
    }

    private function getBaseDir(): string
    {
        return sys_get_temp_dir().'/app-bundle/var/';
    }
}
