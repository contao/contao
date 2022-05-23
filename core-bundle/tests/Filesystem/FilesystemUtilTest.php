<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem;

use Contao\CoreBundle\Filesystem\FilesystemUtil;
use Contao\CoreBundle\Tests\TestCase;

class FilesystemUtilTest extends TestCase
{
    /**
     * @dataProvider provideInvalidArguments
     */
    public function testThrowIfArgumentIsNotAnOpenResource(mixed $argument, string $exception): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($exception);

        FilesystemUtil::assertIsResource($argument);
    }

    public function provideInvalidArguments(): \Generator
    {
        yield 'no resource' => [
            new \stdClass(),
            'Invalid stream provided, expected stream resource, received "object".',
        ];

        $resource = tmpfile();
        fclose($resource);

        yield 'closed resource' => [
            $resource,
            'Invalid stream provided, expected stream resource, received "resource (closed)".',
        ];

        $nonStreamResource = stream_context_create();

        yield 'non-stream resource' => [
            $nonStreamResource,
            'Invalid stream provided, expected stream resource, received resource of type "stream-context".',
        ];
    }

    public function testRewindStream(): void
    {
        $resource1 = tmpfile();
        $resource2 = tmpfile();
        fseek($resource2, 1);

        $this->assertSame(0, ftell($resource1));
        $this->assertSame(1, ftell($resource2));

        FilesystemUtil::rewindStream($resource1);
        FilesystemUtil::rewindStream($resource2);

        $this->assertSame(0, ftell($resource1));
        $this->assertSame(0, ftell($resource2));

        fclose($resource1);
        fclose($resource2);
    }
}
