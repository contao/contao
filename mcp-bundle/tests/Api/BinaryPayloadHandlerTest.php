<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\Api;

use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\RequestBody;
use Contao\CoreBundle\File\UploadSizeProvider;
use Contao\McpBundle\Api\BinaryPayloadHandler;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;

final class BinaryPayloadHandlerTest extends TestCase
{
    public function testUsesTheLowestUploadOperationAndMcpMaximum(): void
    {
        $handler = $this->createHandler(8, 4);
        $operation = $this->createOperation(10);

        $this->assertSame('four', $handler->decode($operation, base64_encode('four')));
        $this->assertSame(
            [
                'contentType' => 'application/octet-stream',
                'encoding' => 'base64',
                'maxDecodedSize' => 4,
                'maxEncodedLength' => 8,
                'supported' => true,
            ],
            $handler->describe($operation),
        );
    }

    public function testRejectsInvalidBase64(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('not valid base64');

        $this->createHandler(10)->decode($this->createOperation(10), '*invalid*');
    }

    public function testRejectsPayloadsAboveTheEffectiveMaximum(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('must not exceed 3 bytes');

        $this->createHandler(10)->decode($this->createOperation(3), base64_encode('four'));
    }

    public function testUsesTheUploadMaximumWithoutAnOperationOrMcpMaximum(): void
    {
        $handler = $this->createHandler(7);
        $operation = $this->createOperation();

        $this->assertSame('content', $handler->decode($operation, base64_encode('content')));
        $this->assertSame(7, $handler->describe($operation)['maxDecodedSize']);
    }

    public function testLeavesNonBinaryPayloadsUnchanged(): void
    {
        $operation = new Put(
            inputFormats: ['xml' => ['application/xml']],
            openapi: new OpenApiOperation(
                requestBody: new RequestBody(content: new \ArrayObject([
                    'application/xml' => new MediaType(new \ArrayObject(['type' => 'string'])),
                ])),
            ),
        );
        $payload = base64_encode('<root/>');

        $this->assertSame($payload, $this->createHandler(10)->decode($operation, $payload));
    }

    private function createHandler(int $maximumUploadSize, int|null $maximumMcpPayloadSize = null): BinaryPayloadHandler
    {
        return new BinaryPayloadHandler(new UploadSizeProvider($maximumUploadSize, $maximumUploadSize), $maximumMcpPayloadSize);
    }

    private function createOperation(int|null $maximumSize = null): Put
    {
        $schema = ['type' => 'string', 'format' => 'binary'];

        if (null !== $maximumSize) {
            $schema['maxLength'] = $maximumSize;
        }

        return new Put(
            inputFormats: ['binary' => ['application/octet-stream']],
            openapi: new OpenApiOperation(
                requestBody: new RequestBody(content: new \ArrayObject([
                    'application/octet-stream' => new MediaType(new \ArrayObject($schema)),
                ])),
            ),
        );
    }
}
