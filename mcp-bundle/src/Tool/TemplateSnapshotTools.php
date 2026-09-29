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

use Contao\CoreBundle\Twig\Studio\TemplateSnapshotException;
use Contao\CoreBundle\Twig\Studio\TemplateSnapshots;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Symfony\Bundle\SecurityBundle\Security;

final class TemplateSnapshotTools
{
    public function __construct(
        private readonly TemplateSnapshots $snapshots,
        private readonly Security $security,
    ) {
    }

    #[McpTool(name: 'contao_template_snapshot', description: 'Create a manual recovery point for the entire project templates/ directory before editing. Returns its hash. History is stored in the application cache and is not a durable backup.', annotations: new ToolAnnotations(destructiveHint: false, openWorldHint: false))]
    public function snapshot(): array
    {
        $this->assertAdmin();

        return $this->execute(fn (): array => ['hash' => $this->snapshots->snapshot()]);
    }

    #[McpTool(name: 'contao_template_snapshots', description: 'List up to 50 recent snapshots for the entire templates/ directory, newest first. Use a returned full hash with contao_template_diff or contao_template_rollback.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function listSnapshots(): array
    {
        $this->assertAdmin();

        return $this->execute(fn (): array => ['snapshots' => $this->snapshots->listSnapshots()]);
    }

    #[McpTool(name: 'contao_template_diff', description: 'Show all changes in the current templates/ directory since a snapshot. Pass its full hash, or omit hash to use the latest snapshot.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function diff(#[Schema(description: 'Full hash returned by contao_template_snapshot or contao_template_snapshots. Omit to use the latest snapshot.', pattern: '^[a-fA-F0-9]{40,64}$')] string|null $hash = null): array
    {
        $this->assertAdmin();

        return $this->execute(fn (): array => ['diff' => $this->snapshots->diff($hash)]);
    }

    #[McpTool(name: 'contao_template_rollback', description: 'Destructively replace the entire templates/ directory with a snapshot. Pass the confirmed target hash, or omit hash for the latest snapshot. Call only when the user explicitly requests restoring that snapshot.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function rollback(#[Schema(description: 'Full hash of the snapshot the user requested to restore. Omit only when the user explicitly selected the latest snapshot.', pattern: '^[a-fA-F0-9]{40,64}$')] string|null $hash = null): array
    {
        $this->assertAdmin();

        return $this->execute(fn (): array => $this->snapshots->rollback($hash));
    }

    private function assertAdmin(): void
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new ToolCallException('Template Studio tools require administrator privileges.');
        }
    }

    private function execute(callable $callback): array
    {
        try {
            return $callback();
        } catch (TemplateSnapshotException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }
    }
}
