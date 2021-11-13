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

use Generator;
use Nucleos\UserAdminBundle\Tests\App\Entity\Group;
use Nucleos\UserAdminBundle\Tests\App\Entity\User;

/**
 * @group integration
 */
final class AdminsTest extends IntegrationTestCase
{
    /**
     * @dataProvider provideCrudUrlsCases
     */
    public function testCrudUrls(string $url): void
    {
        $client = self::createClient();

        $this->prepareData();

        $client->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    /**
     * @return Generator<string[]>
     *
     * @phpstan-return Generator<array{string}>
     */
    public static function provideCrudUrlsCases(): iterable
    {
        yield 'List User' => ['/admin/tests/app/user/list'];
        yield 'Create User' => ['/admin/tests/app/user/create'];
        yield 'Edit User' => ['/admin/tests/app/user/1/edit'];
        yield 'Show User' => ['/admin/tests/app/user/1/show'];
        yield 'Delete User' => ['/admin/tests/app/user/1/delete'];

        yield 'List group' => ['/admin/tests/app/group/list'];
        yield 'Create group' => ['/admin/tests/app/group/create'];
        yield 'Edit group' => ['/admin/tests/app/group/1/edit'];
        yield 'Show group' => ['/admin/tests/app/group/1/show'];
        yield 'Delete group' => ['/admin/tests/app/group/1/delete'];
    }

    private function prepareData(): void
    {
        $manager = $this->getEntityManager();

        $user = new User();
        $user->setUsername('My user');
        $user->setPassword('password');
        $user->setEmail('mail@localhost');

        $manager->persist($user);

        $group = new Group('My group');

        $manager->persist($group);

        $manager->flush();
    }
}
