<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tool;

use Contao\McpBundle\Twig\TwigEnvironmentInspector;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Symfony\Bundle\SecurityBundle\Security;

final class TwigEnvironmentTools
{
    public function __construct(
        private readonly TwigEnvironmentInspector $inspector,
        private readonly Security $security,
    ) {
    }

    #[McpTool(name: 'contao_twig_environment_discover', description: 'Discover registered filters, functions, tests, tags and globals in the application’s current Twig environment. This does not discover templates. Optionally filter by kind and a case-insensitive name substring. Inspect a returned entry before using unfamiliar constructs.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function discover(#[Schema(definition: ['anyOf' => [['type' => 'string', 'enum' => ['filter', 'function', 'test', 'tag', 'global']], ['type' => 'null']]])] string|null $kind = null, string $query = ''): array
    {
        $this->assertAdmin();

        try {
            return $this->inspector->discover($kind, $query);
        } catch (\InvalidArgumentException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }
    }

    #[McpTool(name: 'contao_twig_environment_inspect', description: 'Inspect an exact registered name returned by contao_twig_environment_discover. Returns best-effort callable signatures, tag parser metadata or the declared API of a global. Never executes callables or reads global values. Metadata is not usage documentation or a guarantee of access under Twig security policies.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function inspect(#[Schema(definition: ['type' => 'string', 'enum' => ['filter', 'function', 'test', 'tag', 'global']])] string $kind, string $name): array
    {
        $this->assertAdmin();

        try {
            return $this->inspector->inspect($kind, $name);
        } catch (\InvalidArgumentException|\OutOfBoundsException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }
    }

    private function assertAdmin(): void
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new ToolCallException('Twig environment tools require administrator privileges.');
        }
    }
}
