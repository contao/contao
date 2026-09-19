<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Response;

use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponseConverter
{
    public function convert(Response $response): CallToolResult
    {
        $content = $response->getContent();

        try {
            if (false === $content) {
                throw new \JsonException();
            }

            $result = [
                'status' => $response->getStatusCode(),
                'data' => '' === $content ? null : json_decode($content, false, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING),
            ];
        } catch (\JsonException) {
            return CallToolResult::error([new TextContent(\sprintf('The API returned a non-JSON response (HTTP %s).', $response->getStatusCode()))]);
        }

        return new CallToolResult(
            [new TextContent(json_encode($result, JSON_THROW_ON_ERROR))],
            !$response->isSuccessful(),
            $result,
        );
    }
}
