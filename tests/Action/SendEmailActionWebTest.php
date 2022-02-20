<?php

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
 * @covers \Nucleos\UserAdminBundle\Action\SendEmailAction
 */
final class SendEmailActionWebTest extends WebTestCase
{
    public function testRequest(): void
    {
        $client = static::createClient();

        $client->request('POST', '/admin/resetting/send-email', [
            'username' => 'some-user',
        ]);

        static::assertResponseRedirects('/admin/resetting/check-email?username=some-user');

        $client->followRedirect();

        static::assertResponseIsSuccessful();
    }
}
