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

use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Studio\CacheInvalidator;
use Contao\CoreBundle\Twig\Studio\TemplateSnapshotException;
use Contao\CoreBundle\Twig\Studio\TemplateSnapshots;
use Contao\McpBundle\Tool\TemplateSnapshotTools;
use Contao\TestCase\ContaoTestCase;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;

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
        $snapshots = $this->createSnapshots();

        if (!$snapshots->isAvailable()) {
            $this->markTestSkipped('Git is unavailable.');
        }

        $tools = new TemplateSnapshotTools($snapshots, $this->createAdminSecurity());
        new Filesystem()->dumpFile(self::getTempDir().'/templates/page.twig', 'original');

        $hash = $tools->snapshot()['hash'];
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $hash);
        $this->assertSame($hash, $tools->listSnapshots()['snapshots'][0]['hash']);

        new Filesystem()->dumpFile(self::getTempDir().'/templates/page.twig', 'changed');
        $this->assertStringContainsString('+changed', $tools->diff()['diff']);
        $this->assertSame($tools->diff()['diff'], $tools->diff($hash)['diff']);

        $result = $tools->rollback();
        $this->assertSame($hash, $result['restored']);
        $this->assertSame('original', file_get_contents(self::getTempDir().'/templates/page.twig'));
        $this->assertSame('', $tools->diff()['diff']);
    }

    public function testCanRestoreAnExplicitSnapshot(): void
    {
        $snapshots = $this->createSnapshots();

        if (!$snapshots->isAvailable()) {
            $this->markTestSkipped('Git is unavailable.');
        }

        $tools = new TemplateSnapshotTools($snapshots, $this->createAdminSecurity());

        $filesystem = new Filesystem();
        $filesystem->dumpFile(self::getTempDir().'/templates/page.twig', 'first');

        $first = $tools->snapshot()['hash'];

        $filesystem->dumpFile(self::getTempDir().'/templates/page.twig', 'second');
        $tools->snapshot();

        $this->assertStringContainsString('+second', $tools->diff($first)['diff']);
        $this->assertSame($first, $tools->rollback($first)['restored']);
        $this->assertSame('first', file_get_contents(self::getTempDir().'/templates/page.twig'));
    }

    public function testConvertsSnapshotErrorsToToolCallErrors(): void
    {
        $snapshots = $this->createSnapshots();

        if (!$snapshots->isAvailable()) {
            $this->markTestSkipped('Git is unavailable.');
        }

        $tools = new TemplateSnapshotTools($snapshots, $this->createAdminSecurity());
        $tools->snapshot();

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
        return new TemplateSnapshots(
            self::getTempDir(),
            self::getTempDir().'/var/cache/test',
            $this->createStub(CacheInvalidator::class),
            $this->createStub(ContaoFilesystemLoader::class),
        );
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
