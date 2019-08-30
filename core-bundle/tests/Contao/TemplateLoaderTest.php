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

        $GLOBALS['TL_LANG']['MSC']['global'] = 'global';

        System::setContainer($this->getContainerWithContaoConfiguration($this->getFixturesDir()));
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

    public function testReturnsACustomTemplateInTemplates(): void
    {
        $fs = new Filesystem();
        $fs->touch($this->getFixturesDir().'/templates/mod_article_custom.html5');

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

        $fs->remove($this->getFixturesDir().'/templates/mod_article_custom.html5');

        TemplateLoader::reset();
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

        TemplateLoader::reset();
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

        TemplateLoader::reset();
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

        TemplateLoader::reset();
    }
}
