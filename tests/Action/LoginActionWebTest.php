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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers \Nucleos\UserAdminBundle\Action\LoginAction
 */
final class LoginActionWebTest extends WebTestCase
{
    public function testRequest(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin/login');

        static::assertResponseIsSuccessful();

        $client->submitForm('save', [
            '_username' => 'foo',
            '_password' => 'bar',
        ]);

        static::assertResponseRedirects('http://localhost/admin/login');

        $client->followRedirect();

        static::assertResponseIsSuccessful();
    }
}
