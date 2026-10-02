<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Asset;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Asset\ContaoPackage;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContaoPackageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem())->dumpFile(
            static::getTempDir().'/manifest.json',
            json_encode([
                'backend.js' => '/bundles/contaocore/backend.123.js',
                'relative.js' => 'relative.123.js',
                'external.js' => 'https://external.example.com/external.123.js',
                'protocol-relative.js' => '//external.example.com/external.123.js',
            ], JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @dataProvider getManifestUrls
     */
    public function testGeneratesManifestUrls(string $basePath, string $path, string $expected): void
    {
        $context = $this->createMock(ContextInterface::class);
        $context
            ->method('getBasePath')
            ->willReturn($basePath)
        ;

        $package = new ContaoPackage('bundles/contaocore', new JsonManifestVersionStrategy(static::getTempDir().'/manifest.json'), $context);

        $this->assertSame($expected, $package->getUrl($path));
    }

    public static function getManifestUrls(): iterable
    {
        yield 'assets URL' => ['https://cdn.example.com', 'backend.js', 'https://cdn.example.com/bundles/contaocore/backend.123.js'];
        yield 'assets URL with subdirectory' => ['https://cdn.example.com/site', 'backend.js', 'https://cdn.example.com/site/bundles/contaocore/backend.123.js'];
        yield 'no assets URL' => ['', 'backend.js', '/bundles/contaocore/backend.123.js'];
        yield 'local subdirectory' => ['/site', 'backend.js', '/site/bundles/contaocore/backend.123.js'];
        yield 'relative manifest path' => ['https://cdn.example.com', 'relative.js', 'https://cdn.example.com/bundles/contaocore/relative.123.js'];
        yield 'missing manifest entry' => ['https://cdn.example.com', 'missing.js', 'https://cdn.example.com/bundles/contaocore/missing.js'];
        yield 'absolute manifest URL' => ['https://cdn.example.com', 'external.js', 'https://external.example.com/external.123.js'];
        yield 'protocol-relative manifest URL' => ['https://cdn.example.com', 'protocol-relative.js', '//external.example.com/external.123.js'];
        yield 'absolute input URL' => ['https://cdn.example.com', 'https://external.example.com/backend.js', 'https://external.example.com/backend.js'];
        yield 'protocol-relative input URL' => ['https://cdn.example.com', '//external.example.com/backend.js', '//external.example.com/backend.js'];
    }

    public function testPreservesStaticVersioning(): void
    {
        $context = $this->createMock(ContextInterface::class);
        $context
            ->method('getBasePath')
            ->willReturn('https://cdn.example.com')
        ;

        $package = new ContaoPackage('assets/jquery', new StaticVersionStrategy('1.2.3', '%s?v=%s'), $context);

        $this->assertSame('https://cdn.example.com/assets/jquery/jquery.js?v=1.2.3', $package->getUrl('jquery.js'));
        $this->assertSame('1.2.3', $package->getVersion('jquery.js'));
    }

    /**
     * @dataProvider getRootPageUrls
     */
    public function testUsesTheRootPageAssetsUrl(string $assetsUrl, bool $useSSL, bool $debug, string $expected): void
    {
        $page = $this->createMock(PageModel::class);
        $page
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        $page
            ->method('__get')
            ->willReturnMap([
                ['staticPlugins', $assetsUrl],
                ['rootUseSSL', $useSSL],
            ])
        ;

        $request = Request::create(
            'https://example.com/site/index.php',
            server: [
                'SCRIPT_FILENAME' => '/site/index.php',
                'SCRIPT_NAME' => '/site/index.php',
            ],
        );

        $request->attributes->set('pageModel', $page);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $context = new ContaoContext($requestStack, 'staticPlugins', $debug);
        $package = new ContaoPackage('bundles/contaocore', new JsonManifestVersionStrategy(static::getTempDir().'/manifest.json'), $context);

        $this->assertSame($expected, $package->getUrl('backend.js'));
    }

    public static function getRootPageUrls(): iterable
    {
        yield 'HTTPS' => ['cdn.example.com', true, false, 'https://cdn.example.com/site/bundles/contaocore/backend.123.js'];
        yield 'HTTP' => ['cdn.example.com', false, false, 'http://cdn.example.com/site/bundles/contaocore/backend.123.js'];
        yield 'debug mode' => ['cdn.example.com', true, true, '/site/bundles/contaocore/backend.123.js'];
        yield 'no assets URL' => ['', true, false, '/site/bundles/contaocore/backend.123.js'];
    }
}
