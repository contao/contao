<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\Tool;

use Contao\CoreBundle\Twig\Studio\TemplateSnapshotException;
use Contao\CoreBundle\Twig\Studio\TemplateSnapshots;
use Contao\McpBundle\Tool\TemplateSnapshotTools;
use Contao\TestCase\ContaoTestCase;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\SecurityBundle\Security;

final class TemplateSnapshotToolsTest extends ContaoTestCase
{
    public function testRequiresAdministratorForEveryTool(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(4))
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false)
        ;

        $tools = new TemplateSnapshotTools($this->createSnapshots(), $security);

        foreach (['snapshot', 'listSnapshots', 'diff', 'rollback'] as $method) {
            try {
                $tools->$method();

                $this->fail($method.' should require an administrator.');
            } catch (ToolCallException $exception) {
                $this->assertSame('Template Studio tools require administrator privileges.', $exception->getMessage());
            }
        }
    }

    public function testCreatesListsComparesAndRestoresTheLatestManualSnapshot(): void
    {
        $hash = str_repeat('a', 40);

        $snapshots = $this->createMock(TemplateSnapshots::class);
        $snapshots
            ->expects($this->once())
            ->method('snapshot')
            ->willReturn($hash)
        ;
        $snapshots
            ->expects($this->once())
            ->method('listSnapshots')
            ->willReturn([['hash' => $hash]])
        ;
        $snapshots
            ->expects($this->once())
            ->method('diff')
            ->with(null)
            ->willReturn('template diff')
        ;
        $snapshots
            ->expects($this->once())
            ->method('rollback')
            ->with(null)
            ->willReturn(['restored' => $hash])
        ;

        $tools = new TemplateSnapshotTools($snapshots, $this->createAdminSecurity());

        $this->assertSame(['hash' => $hash], $tools->snapshot());
        $this->assertSame(['snapshots' => [['hash' => $hash]]], $tools->listSnapshots());
        $this->assertSame(['diff' => 'template diff'], $tools->diff());
        $this->assertSame(['restored' => $hash], $tools->rollback());
    }

    public function testCanRestoreAnExplicitSnapshot(): void
    {
        $first = str_repeat('b', 40);

        $snapshots = $this->createMock(TemplateSnapshots::class);
        $snapshots
            ->expects($this->once())
            ->method('diff')
            ->with($first)
            ->willReturn('explicit snapshot diff')
        ;
        $snapshots
            ->expects($this->once())
            ->method('rollback')
            ->with($first)
            ->willReturn(['restored' => $first])
        ;

        $tools = new TemplateSnapshotTools($snapshots, $this->createAdminSecurity());

        $this->assertSame(['diff' => 'explicit snapshot diff'], $tools->diff($first));
        $this->assertSame(['restored' => $first], $tools->rollback($first));
    }

    public function testConvertsSnapshotErrorsToToolCallErrors(): void
    {
        $snapshots = $this->createMock(TemplateSnapshots::class);
        $snapshots
            ->expects($this->once())
            ->method('diff')
            ->with('invalid')
            ->willThrowException(new TemplateSnapshotException('Invalid template snapshot hash.'))
        ;

        $tools = new TemplateSnapshotTools($snapshots, $this->createAdminSecurity());

        try {
            $tools->diff('invalid');

            $this->fail('Comparing with an invalid hash should fail.');
        } catch (ToolCallException $exception) {
            $this->assertSame('Invalid template snapshot hash.', $exception->getMessage());
            $this->assertInstanceOf(TemplateSnapshotException::class, $exception->getPrevious());
        }
    }

    private function createSnapshots(): TemplateSnapshots
    {
        return $this->createStub(TemplateSnapshots::class);
    }

    private function createAdminSecurity(): Security
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->atLeastOnce())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true)
        ;

        return $security;
    }
}
