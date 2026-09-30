<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Api\Files;

use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\E2eTests\Api\ApiTestTrait;

class FilesTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    public function testUploadsAndMovesAFile(): void
    {
        // Uploaded files are not reset between runs, so use unique names
        $name = 'api-'.bin2hex(random_bytes(4));
        $source = 'media/'.$name.'.txt';
        $destination = 'media/'.$name.'-moved.txt';

        [$status] = $this->apiRequest('PUT', '/contao/api/files/'.$source, 'Uploaded via API', 'application/octet-stream');

        $this->assertLessThan(300, $status);

        [$status] = $this->apiRequest('GET', '/contao/api/files/'.$source);

        $this->assertSame(200, $status);

        [$status] = $this->apiRequest('POST', '/contao/api/files_operations/move', ['source' => $source, 'destination' => $destination]);

        $this->assertSame(200, $status);

        [$status] = $this->apiRequest('GET', '/contao/api/files/'.$source);

        $this->assertSame(404, $status);

        [$status] = $this->apiRequest('GET', '/contao/api/files/'.$destination);

        $this->assertSame(200, $status);
    }
}
