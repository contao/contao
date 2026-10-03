<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Api\Authentication;

use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\E2eTests\Api\ApiTestTrait;

class AuthenticationTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    public function testRequiresAnAccessToken(): void
    {
        [$status] = $this->apiRequest('GET', '/contao/api/dc/news_archive', token: null);

        $this->assertSame(401, $status);

        [$status] = $this->apiRequest('GET', '/contao/api/dc/news_archive');

        $this->assertSame(200, $status);
    }
}
