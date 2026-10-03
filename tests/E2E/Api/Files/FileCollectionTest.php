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

class FileCollectionTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    public function testListsFilesOfAFolder(): void
    {
        // Uploaded files are not reset between runs, so use a unique folder
        $folder = 'media/api-'.bin2hex(random_bytes(4));
        $file = $folder.'/file.txt';
        $nested = $folder.'/nested/file.txt';

        $this->apiRequest('PUT', '/contao/api/files/'.$file, 'File', 'application/octet-stream');
        $this->apiRequest('PUT', '/contao/api/files/'.$nested, 'Nested file', 'application/octet-stream');

        // Without "deep", only the direct children are listed
        [$status, $collection] = $this->apiRequest('GET', '/contao/api/files?path='.$folder);

        $this->assertSame(200, $status);

        $paths = array_column($this->members($collection), 'path');

        $this->assertContains($file, $paths);
        $this->assertNotContains($nested, $paths);

        [$status, $collection] = $this->apiRequest('GET', '/contao/api/files?path='.$folder.'&deep=1');

        $this->assertSame(200, $status);
        $this->assertContains($nested, array_column($this->members($collection), 'path'));
    }

    public function testReadsAFile(): void
    {
        $path = 'media/api-'.bin2hex(random_bytes(4)).'.txt';

        $this->apiRequest('PUT', '/contao/api/files/'.$path, 'Readable', 'application/octet-stream');

        [$status, $file] = $this->apiRequest('GET', '/contao/api/files/'.$path);

        $this->assertSame(200, $status);
        $this->assertSame($path, $file['path']);
        $this->assertTrue($file['isFile']);
        $this->assertSame(8, $file['fileSize']);
    }

    public function testReturnsNotFoundForAnUnknownFile(): void
    {
        [$status] = $this->apiRequest('GET', '/contao/api/files/media/does-not-exist.txt');

        $this->assertSame(404, $status);
    }
}
