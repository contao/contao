<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\IDE;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Ide\NamespaceLookupFileGenerator;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Symfony\Component\Filesystem\Filesystem;

class NamespaceLookupFileGeneratorTest extends TestCase
{
    public function testWritesFile(): void
    {
        $loader = $this->createStub(ContaoFilesystemLoader::class);
        $loader
            ->method('getInheritanceChains')
            ->willReturn([
                'a' => [
                    '/project/templates/a.html.twig' => '@Contao_Global/a.html.twig',
                    '/project/contao/templates/a.html.twig' => '@Contao_App/a.html.twig',
                ],
                'b' => [
                    '/project/templates/b.html.twig' => '@Contao_Global/b.html.twig',
                ],
                'foo/c' => [
                    '/project/templates/foo/c.html.twig' => '@Contao_Global/foo/c.html.twig',
                ],
                'bar/d' => [
                    '/project/vendor/demo/bar/d.html.twig' => '@Contao_DemoBundle/bar/d.html.twig',
                ],
            ])
        ;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with(
                '/project/foo/bar/ide-twig.json',
                $this->callback(
                    function (string $json): bool {
                        $expectedData = [
                            'namespaces' => [
                                ['namespace' => 'Contao', 'path' => '../../templates'],
                                ['namespace' => 'Contao_Global', 'path' => '../../templates'],
                                ['namespace' => 'Contao', 'path' => '../../contao/templates'],
                                ['namespace' => 'Contao_App', 'path' => '../../contao/templates'],
                                ['namespace' => 'Contao', 'path' => '../../vendor/demo'],
                                ['namespace' => 'Contao_DemoBundle', 'path' => '../../vendor/demo'],
                            ],
                        ];

                        $this->assertJson($json);
                        $this->assertSame($expectedData, json_decode($json, true, 512, JSON_THROW_ON_ERROR));

                        return true;
                    },
                ),
            )
        ;

        $namespaceLookupFileGenerator = new NamespaceLookupFileGenerator($loader, $filesystem);
        $namespaceLookupFileGenerator->write('/project/foo/bar');
    }
}
