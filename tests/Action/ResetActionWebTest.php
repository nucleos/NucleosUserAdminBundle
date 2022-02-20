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
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \Nucleos\UserAdminBundle\Action\ResetAction
 */
final class ResetActionWebTest extends WebTestCase
{
    public function testRequestInvalidToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin/resetting/reset/my-token');

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
