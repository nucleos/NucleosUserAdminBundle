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
 * @covers \Nucleos\UserAdminBundle\Action\CheckEmailAction
 */
final class CheckEmailActionWebTest extends WebTestCase
{
    public function testRequest(): void
    {
        $client = self::createClient();

        $client->request('GET', '/admin/resetting/check-email');

        self::assertResponseRedirects('/admin/resetting/request');

        $client->followRedirect();

        self::assertResponseIsSuccessful();
    }
}
