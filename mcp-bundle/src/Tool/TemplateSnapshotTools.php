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

    #[McpTool(name: 'contao_template_snapshot', description: 'Manually snapshot the entire templates directory in short-lived cache history.', annotations: new ToolAnnotations(destructiveHint: false, openWorldHint: false))]
    public function snapshot(): array
    {
        $this->assertAdmin();

        return $this->execute(fn (): array => ['hash' => $this->snapshots->snapshot()]);
    }

    #[McpTool(name: 'contao_template_snapshots', description: 'List template snapshots, including safety snapshots created before a restore.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function listSnapshots(): array
    {
        $this->assertAdmin();

        return $this->execute(fn (): array => ['snapshots' => $this->snapshots->listSnapshots()]);
    }

    #[McpTool(name: 'contao_template_diff', description: 'Compare the current templates directory with a snapshot. Omit hash for the latest manual snapshot.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function diff(string|null $hash = null): array
    {
        $this->assertAdmin();

        return $this->execute(fn (): array => ['diff' => $this->snapshots->diff($hash)]);
    }

    #[McpTool(name: 'contao_template_rollback', description: 'Restore the entire templates directory to a snapshot. Omit hash for the latest manual snapshot. Creates a safety snapshot of the current directory first.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function rollback(string|null $hash = null): array
    {
        $this->assertAdmin();

        return $this->execute(fn (): array => $this->snapshots->rollback($hash));
    }

    private function assertAdmin(): void
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new ToolCallException('Template snapshots require administrator privileges.');
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
