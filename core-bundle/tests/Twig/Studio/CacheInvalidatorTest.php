<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Studio;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Studio\CacheInvalidator;
use Twig\Environment;

class CacheInvalidatorTest extends TestCase
{
    public function testInvalidatesCache(): void
    {
        $expectedTemplatesToBeInvalidated = [
            '@Contao_specific/foo.html.twig',
            '@Contao_specific/bar.html.twig',
        ];

        $invalidatedTemplates = [];

        $twig = $this->createStub(Environment::class);
        $twig
            ->method('removeCache')
            ->willReturnCallback(
                static function (string $logicalName) use (&$invalidatedTemplates): void {
                    $invalidatedTemplates[] = $logicalName;
                },
            )
        ;

        $loader = $this->createStub(ContaoFilesystemLoader::class);
        $loader
            ->method('getInheritanceChains')
            ->willReturn([
                'backend/baz' => [
                    'path/to/baz.html.twig' => '@Contao_specific/baz.html.twig',
                ],
                'foo' => [
                    'path/to/foo.html.twig' => '@Contao_specific/foo.html.twig',
                ],
                'bar' => [
                    'path/to/bar.html.twig' => '@Contao_specific/bar.html.twig',
                ],
            ])
        ;

        (new CacheInvalidator($twig, $loader))->invalidateCache('@Contao/foo.html.twig');

        $this->assertEqualsCanonicalizing($expectedTemplatesToBeInvalidated, $invalidatedTemplates);
    }
}
