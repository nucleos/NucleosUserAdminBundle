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

namespace Nucleos\UserAdminBundle\Tests\Action;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;

abstract class IntegrationTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        $kernel = self::createKernel();
        $kernel->boot();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $application->run(new ArrayInput([
            'command' => 'doctrine:database:drop',
            '--force' => '1',
            '--quiet' => '1',
        ]));

        $application->run(new ArrayInput([
            'command' => 'doctrine:database:create',
            '--quiet' => '1',
        ]));

        $application->run(new ArrayInput([
            'command' => 'doctrine:schema:create',
            '--quiet' => '1',
        ]));

        $kernel->shutdown();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        static::$class = null;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        $manager = self::$container->get('doctrine.orm.entity_manager');

        \assert($manager instanceof EntityManagerInterface);

        return $manager;
    }
}
