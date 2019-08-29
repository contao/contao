<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Controller;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Contao\TemplateLoader;
use Symfony\Component\Filesystem\Filesystem;

class TemplateLoaderTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $fs = new Filesystem();
        $fs->mkdir($this->getFixturesDir().'/templates');
        $fs->touch($this->getFixturesDir().'/templates/mod_article_custom.html5');
        $fs->touch($this->getFixturesDir().'/templates/mod_article_list_custom.html5');

        $GLOBALS['TL_LANG']['MSC']['global'] = 'global';

        System::setContainer($this->getContainerWithContaoConfiguration($this->getFixturesDir()));

        TemplateLoader::addFile('ctlg_views', 'src/Resources/contao/templates');
        TemplateLoader::addFile('ctlg_view_master', 'src/Resources/contao/templates');
        TemplateLoader::addFile('ctlg_view_teaser', 'src/Resources/contao/templates');
        TemplateLoader::addFile('mod_article', 'src/Resources/contao/templates/modules');
        TemplateLoader::addFile('mod_article_list', 'src/Resources/contao/templates/modules');
        TemplateLoader::addFile('mod_article_test', 'contao/templates');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove($this->getFixturesDir().'/templates');

        unset($GLOBALS['TL_LANG']);
    }

    /**
     * @dataProvider getTemplates
     */
    public function testReturnsTheTemplatesOfAGroup(string $prefix, bool $separate, array $templates): void
    {
        $this->assertSame($templates, Controller::getTemplateGroup($prefix, $separate));
    }

    public function getTemplates(): \Generator
    {
        yield [
            'mod',
            false,
            [
                'mod_article' => 'mod_article',
                'mod_article_custom' => 'mod_article_custom (global)',
                'mod_article_list' => 'mod_article_list',
                'mod_article_list_custom' => 'mod_article_list_custom (global)',
                'mod_article_test' => 'mod_article_test',
            ],
        ];

        yield [
            'mod_',
            false,
            [
                'mod_article' => 'mod_article',
                'mod_article_custom' => 'mod_article_custom (global)',
                'mod_article_list' => 'mod_article_list',
                'mod_article_list_custom' => 'mod_article_list_custom (global)',
                'mod_article_test' => 'mod_article_test',
            ],
        ];

        yield [
            'mod_article',
            false,
            [
                'mod_article' => 'mod_article',
                'mod_article_custom' => 'mod_article_custom (global)',
                'mod_article_list' => 'mod_article_list',
                'mod_article_list_custom' => 'mod_article_list_custom (global)',
                'mod_article_test' => 'mod_article_test',
            ],
        ];

        yield [
            'mod_article_',
            false,
            [
                'mod_article_custom' => 'mod_article_custom (global)',
                'mod_article_list' => 'mod_article_list',
                'mod_article_list_custom' => 'mod_article_list_custom (global)',
                'mod_article_test' => 'mod_article_test',
            ],
        ];

        yield [
            'mod_article',
            true,
            [
                'mod_article' => 'mod_article',
                'mod_article_custom' => 'mod_article_custom (global)',
                'mod_article_test' => 'mod_article_test',
            ],
        ];

        yield [
            'mod_article_',
            true,
            [
                'mod_article_custom' => 'mod_article_custom (global)',
                'mod_article_test' => 'mod_article_test',
            ],
        ];

        yield [
            'ctlg_view',
            false,
            [
                'ctlg_view_master' => 'ctlg_view_master',
                'ctlg_view_teaser' => 'ctlg_view_teaser',
            ],
        ];

        yield [
            'ctlg_view_',
            false,
            [
                'ctlg_view_master' => 'ctlg_view_master',
                'ctlg_view_teaser' => 'ctlg_view_teaser',
            ],
        ];

        yield ['ctlg_view', true, []];
        yield ['ctlg_view_', true, []];
    }

    /**
     * @dataProvider getInvalidArguments
     */
    public function testThrowsAnExceptionIfTheArgumentsAreInvalid(string $prefix, bool $separate): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Cannot separate templates if only a prefix is given');

        Controller::getTemplateGroup($prefix, $separate);
    }

    public function getInvalidArguments(): \Generator
    {
        yield ['mod', true];
        yield ['mod_', true];
    }
}
