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

use Contao\ContentText;
use Contao\Controller;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FormTextField;
use Contao\ModuleArticleList;
use Contao\System;
use Contao\TemplateLoader;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class TemplateLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem())->mkdir(Path::join($this->getTempDir(), 'templates'));

        $GLOBALS['TL_CTE'] = [
            'texts' => [
                'text' => ContentText::class,
            ],
        ];

        $GLOBALS['TL_FFL'] = [
            'text' => FormTextField::class,
        ];

        $GLOBALS['FE_MOD'] = [
            'miscellaneous' => [
                'article_list' => ModuleArticleList::class,
            ],
        ];

        $GLOBALS['TL_LANG']['MSC']['global'] = 'global';

        System::setContainer($this->getContainerWithContaoConfiguration($this->getTempDir()));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem())->remove(Path::join($this->getTempDir(), 'templates'));

        TemplateLoader::reset();

        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_CTE'], $GLOBALS['TL_FFL'], $GLOBALS['FE_MOD']);
    }

    public function testReturnsACustomTemplateInTemplates(): void
    {
        (new Filesystem())->touch(Path::join($this->getTempDir(), 'templates/mod_article_custom.html5'));

        TemplateLoader::addFile('mod_article', 'core-bundle/src/Resources/contao/templates/modules');

        $this->assertSame(
            [
                'mod_article' => 'mod_article',
                'mod_article_custom' => 'mod_article_custom (global)',
            ],
            Controller::getTemplateGroup('mod_article')
        );

        $this->assertSame(
            [
                'mod_article_custom' => 'mod_article_custom (global)',
            ],
            Controller::getTemplateGroup('mod_article_')
        );
    }

    public function testReturnsACustomTemplateInContaoTemplates(): void
    {
        TemplateLoader::addFile('mod_article', 'core-bundle/src/Resources/contao/templates/modules');
        TemplateLoader::addFile('mod_article_custom', 'contao/templates');

        $this->assertSame(
            [
                'mod_article' => 'mod_article',
                'mod_article_custom' => 'mod_article_custom',
            ],
            Controller::getTemplateGroup('mod_article')
        );

        $this->assertSame(
            [
                'mod_article_custom' => 'mod_article_custom',
            ],
            Controller::getTemplateGroup('mod_article_')
        );
    }

    public function testReturnsACustomTemplateInAnotherBundle(): void
    {
        TemplateLoader::addFile('mod_article', 'core-bundle/src/Resources/contao/templates/modules');
        TemplateLoader::addFile('mod_article_custom', 'article-bundle/src/Resources/contao/templates/modules');

        $this->assertSame(
            [
                'mod_article' => 'mod_article',
                'mod_article_custom' => 'mod_article_custom',
            ],
            Controller::getTemplateGroup('mod_article')
        );

        $this->assertSame(
            [
                'mod_article_custom' => 'mod_article_custom',
            ],
            Controller::getTemplateGroup('mod_article_')
        );
    }

    public function testReturnsMultipleRootTemplatesWithTheSamePrefix(): void
    {
        TemplateLoader::addFile('ctlg_views', 'catalog-manager/src/Resources/contao/templates');
        TemplateLoader::addFile('ctlg_view_master', 'catalog-manager/src/Resources/contao/templates');
        TemplateLoader::addFile('ctlg_view_teaser', 'catalog-manager/src/Resources/contao/templates');

        $this->assertSame(
            [
                'ctlg_view_master' => 'ctlg_view_master',
                'ctlg_view_teaser' => 'ctlg_view_teaser',
            ],
            Controller::getTemplateGroup('ctlg_view')
        );

        $this->assertSame(
            [
                'ctlg_view_master' => 'ctlg_view_master',
                'ctlg_view_teaser' => 'ctlg_view_teaser',
            ],
            Controller::getTemplateGroup('ctlg_view_')
        );
    }

    public function testReturnsATemplateGroup(): void
    {
        (new Filesystem())->touch([
            Path::join($this->getTempDir(), 'templates/mod_article_custom.html5'),
            Path::join($this->getTempDir(), 'templates/mod_article_list_custom.html5'),
        ]);

        TemplateLoader::addFile('mod_article', 'core-bundle/src/Resources/contao/templates/modules');
        TemplateLoader::addFile('mod_article_list', 'core-bundle/src/Resources/contao/templates/modules');
        TemplateLoader::addFile('mod_article_foo', 'article-bundle/src/Resources/contao/templates/modules');
        TemplateLoader::addFile('mod_article_bar', 'contao/templates');

        $this->assertSame(
            [
                'mod_article' => 'mod_article',
                'mod_article_bar' => 'mod_article_bar',
                'mod_article_custom' => 'mod_article_custom (global)',
                'mod_article_foo' => 'mod_article_foo',
            ],
            Controller::getTemplateGroup('mod_article')
        );

        $this->assertSame(
            [
                'mod_article_bar' => 'mod_article_bar',
                'mod_article_custom' => 'mod_article_custom (global)',
                'mod_article_foo' => 'mod_article_foo',
            ],
            Controller::getTemplateGroup('mod_article_')
        );

        $this->assertSame(
            [
                'mod_article_list' => 'mod_article_list',
                'mod_article_list_custom' => 'mod_article_list_custom (global)',
            ],
            Controller::getTemplateGroup('mod_article_list')
        );

        $this->assertSame(
            [
                'mod_article_list_custom' => 'mod_article_list_custom (global)',
            ],
            Controller::getTemplateGroup('mod_article_list_')
        );
    }

    public function testSupportsAdditionalMappers(): void
    {
        $GLOBALS['CTLG'] = [
            'view' => 'Ctlg\View',
            'view_details' => 'Ctlg\ViewDetails',
        ];

        TemplateLoader::addFile('ctlg_view', 'catalog-manager/src/Resources/contao/templates');
        TemplateLoader::addFile('ctlg_view_details', 'catalog-manager/src/Resources/contao/templates');

        $this->assertSame(
            [
                'ctlg_view' => 'ctlg_view',
                'ctlg_view_details' => 'ctlg_view_details',
            ],
            Controller::getTemplateGroup('ctlg_view')
        );

        $this->assertSame(
            [
                'ctlg_view' => 'ctlg_view',
            ],
            Controller::getTemplateGroup('ctlg_view', ['ctlg' => array_keys($GLOBALS['CTLG'])])
        );

        unset($GLOBALS['CTLG']);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using hyphens in the template name "mod_article-custom.html5" has been deprecated %s.
     */
    public function testSupportsHyphensInCustomTemplateNames(): void
    {
        (new Filesystem())->touch([
            Path::join($this->getTempDir(), '/templates/mod_article-custom.html5'),
            Path::join($this->getTempDir(), '/templates/mod_article_custom.html5'),
        ]);

        TemplateLoader::addFile('mod_article', 'core-bundle/src/Resources/contao/templates/modules');

        $this->assertSame(
            [
                'mod_article' => 'mod_article',
                'mod_article-custom' => 'mod_article-custom (global)',
                'mod_article_custom' => 'mod_article_custom (global)',
            ],
            Controller::getTemplateGroup('mod_article')
        );

        $this->assertSame(
            [
                'mod_article-custom' => 'mod_article-custom (global)',
                'mod_article_custom' => 'mod_article_custom (global)',
            ],
            Controller::getTemplateGroup('mod_article_')
        );
    }
}
