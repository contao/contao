<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Api\DataContainer;

use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\E2eTests\Api\ApiTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

class ResourceTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    /**
     * @throws \JsonException
     */
    #[DataProvider('resources')]
    public function testCreatesResource(string $path, array $create): void
    {
        [$status, $response] = $this->apiRequest('POST', $path, $create);

        $this->assertSame(201, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $response);
    }

    /**
     * @throws \JsonException
     */
    #[DataProvider('resources')]
    public function testReadsResource(string $path, array $create): void
    {
        [$status, $response] = $this->apiRequest('POST', $path, $create);

        $this->assertSame(201, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $response);

        [$status, $response] = $this->apiRequest('GET', $response['@id']);

        $this->assertSame(200, $status, json_encode($response, JSON_PRETTY_PRINT));
    }

    /**
     * @throws \JsonException
     */
    #[DataProvider('updatableResources')]
    public function testUpdatesResource(string $path, array $create, array $update): void
    {
        [$status, $response] = $this->apiRequest('POST', $path, $create);

        $this->assertSame(201, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $response);

        [$status, $response] = $this->apiRequest('PATCH', $response['@id'], $update);

        $this->assertSame(200, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertSame($update['title'], $response['title']);
    }

    /**
     * @throws \JsonException
     */
    #[DataProvider('resources')]
    public function testDeletesResource(string $path, array $create): void
    {
        [$status, $response] = $this->apiRequest('POST', $path, $create);

        $this->assertSame(201, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $response);

        $id = $response['@id'];

        [$status] = $this->apiRequest('DELETE', $id);

        $this->assertSame(204, $status);

        [$status] = $this->apiRequest('GET', $id);

        $this->assertSame(404, $status);
    }

    /**
     * @return \Generator<string, array{string, array<string, mixed>}>
     */
    public static function resources(): iterable
    {
        yield 'page' => [
            '/dc/page',
            [
                'type' => 'regular',
                'title' => 'API page',
            ],
        ];
    }

    /**
     * @return \Generator<string, array{string, array<string, mixed>, array<string, mixed>}>
     */
    public static function updatableResources(): iterable
    {
        yield 'page' => [
            '/dc/page',
            [
                'type' => 'regular',
                'title' => 'API page',
            ],
            [
                'title' => 'Updated API page',
            ],
        ];
    }
}
