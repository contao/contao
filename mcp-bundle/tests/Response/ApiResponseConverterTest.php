<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\Response;

use Contao\McpBundle\Response\ApiResponseConverter;
use Mcp\Schema\Content\BlobResourceContents;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\TextContent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ApiResponseConverterTest extends TestCase
{
    public function testPreservesEmptyJsonObjects(): void
    {
        $result = new ApiResponseConverter()->convert(new Response('{}'));

        $this->assertFalse($result->isError);
        $this->assertInstanceOf(TextContent::class, $result->content[0]);
        $this->assertSame('{"status":200,"data":{}}', $result->content[0]->text);
    }

    public function testPreservesJsonObjectsAndLargeIdentifiers(): void
    {
        $response = new Response('{"empty":{},"list":[],"id":9223372036854775808}');
        $result = new ApiResponseConverter()->convert($response);

        $this->assertInstanceOf(TextContent::class, $result->content[0]);
        $this->assertSame('{"status":200,"data":{"empty":{},"list":[],"id":"9223372036854775808"}}', $result->content[0]->text);
    }

    public function testConvertsDeclaredResourceResponsesToEmbeddedResources(): void
    {
        $response = new StreamedResponse(
            static function (): void {
                echo '{"type":"downloaded JSON file"}';
            },
        );
        $response->headers->set('Content-Type', 'application/json');

        $result = new ApiResponseConverter()->convert(
            $response,
            'https://example.org/contao/api/files_operations/download/file.json',
            outputIsResource: true,
        );

        $this->assertFalse($result->isError);
        $this->assertInstanceOf(EmbeddedResource::class, $result->content[0]);
        $this->assertInstanceOf(BlobResourceContents::class, $result->content[0]->resource);
        $this->assertSame('https://example.org/contao/api/files_operations/download/file.json', $result->content[0]->resource->uri);
        $this->assertSame('application/json', $result->content[0]->resource->mimeType);
        $this->assertSame('{"type":"downloaded JSON file"}', base64_decode($result->content[0]->resource->blob, true));
        $this->assertSame(
            [
                'status' => 200,
                'data' => [
                    'uri' => 'https://example.org/contao/api/files_operations/download/file.json',
                    'mimeType' => 'application/json',
                    'size' => 31,
                ],
            ],
            $result->structuredContent,
        );
    }

    public function testRejectsNonJsonResponsesThatAreNotAttachments(): void
    {
        $response = new StreamedResponse(
            static function (): void {
                echo '<html>Login</html>';
            },
        );

        $this->assertTrue(new ApiResponseConverter()->convert($response)->isError);
    }

    #[DataProvider('provideErrors')]
    public function testReturnsApiErrorsAsToolErrors(int $status, string $body): void
    {
        $response = new Response($body, $status);
        $result = new ApiResponseConverter()->convert($response);

        $this->assertTrue($result->isError);
        $this->assertSame($status, $result->structuredContent['status']);
        $this->assertJsonStringEqualsJsonString($body, json_encode($result->structuredContent['data'], JSON_THROW_ON_ERROR));
    }

    public static function provideErrors(): iterable
    {
        yield 'permission' => [403, '{"detail":"Access denied"}'];
        yield 'missing record' => [404, '{"detail":"Not found"}'];
        yield 'validation' => [422, '{"violations":[{"propertyPath":"title","message":"Required"}]}'];
    }

    public function testHandlesEmptyDeleteResponse(): void
    {
        $response = new Response('', 204);
        $result = new ApiResponseConverter()->convert($response);

        $this->assertFalse($result->isError);
        $this->assertSame(['status' => 204, 'data' => null], $result->structuredContent);
    }

    public function testDoesNotTreatHtmlOrRedirectsAsSuccess(): void
    {
        $response = new Response('<html>Login</html>', 302);
        $result = new ApiResponseConverter()->convert($response);

        $this->assertTrue($result->isError);
        $this->assertInstanceOf(TextContent::class, $result->content[0]);
        $this->assertStringContainsString('HTTP 302', $result->content[0]->text);
    }
}
