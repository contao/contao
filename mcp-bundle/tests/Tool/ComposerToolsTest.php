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

use Composer\InstalledVersions;
use Contao\McpBundle\Tool\ComposerTools;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
final class ComposerToolsTest extends TestCase
{
    protected function setUp(): void
    {
        InstalledVersions::reload([
            'root' => InstalledVersions::getRootPackage(),
            'versions' => [
                'example/package' => ['version' => '1.2.3.0', 'pretty_version' => 'v1.2.3', 'dev_requirement' => false],
                'example/development' => ['version' => 'dev-main', 'pretty_version' => 'dev-main', 'dev_requirement' => true],
                'example/replaced' => ['replaced' => ['^2.0'], 'dev_requirement' => false],
                'example/provided' => ['provided' => ['1.0'], 'dev_requirement' => false],
            ],
        ]);
    }

    #[DataProvider('packageVersions')]
    public function testReturnsInstalledVersions(string $package, string|null $version, string|null $prettyVersion): void
    {
        $tools = new ComposerTools();

        $this->assertSame(['package' => $package, 'version' => $version, 'prettyVersion' => $prettyVersion], $tools->version($package));
    }

    public static function packageVersions(): iterable
    {
        yield 'release' => ['example/package', '1.2.3.0', 'v1.2.3'];
        yield 'development dependency' => ['example/development', 'dev-main', 'dev-main'];
        yield 'replaced package' => ['example/replaced', null, null];
        yield 'provided package' => ['example/provided', null, null];
    }

    public function testReportsUnknownPackagesAsToolErrors(): void
    {
        $tools = new ComposerTools();

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Package "example/missing" is not installed');

        $tools->version('example/missing');
    }
}
