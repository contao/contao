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

use Contao\Config;
use Contao\ContentText;
use Contao\Controller;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\FormTextField;
use Contao\ModuleArticleList;
use Contao\System;
use Contao\TemplateLoader;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class TemplateLoaderTest extends TestCase
{
    use ExpectDeprecationTrait;

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

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('contao.twig.filesystem_loader', $this->createMock(TemplateHierarchyInterface::class));

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(Path::join($this->getTempDir(), 'templates'));

        TemplateLoader::reset();

        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_CTE'], $GLOBALS['TL_FFL'], $GLOBALS['FE_MOD'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([DcaExtractor::class, DcaLoader::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testReturnsACustomTemplateInTemplates(): void
    {
        (new Filesystem())->touch(Path::join($this->getTempDir(), 'templates/mod_article_custom.html5'));

        TemplateLoader::addFile('mod_article', 'core-bundle/contao/templates/modules');

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
        TemplateLoader::addFile('mod_article', 'core-bundle/contao/templates/modules');
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
        TemplateLoader::addFile('mod_article', 'core-bundle/contao/templates/modules');
        TemplateLoader::addFile('mod_article_custom', 'article-bundle/contao/templates/modules');

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
        TemplateLoader::addFile('ctlg_views', 'catalog-manager/contao/templates');
        TemplateLoader::addFile('ctlg_view_master', 'catalog-manager/contao/templates');
        TemplateLoader::addFile('ctlg_view_teaser', 'catalog-manager/contao/templates');

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

        TemplateLoader::addFile('mod_article', 'core-bundle/contao/templates/modules');
        TemplateLoader::addFile('mod_article_list', 'core-bundle/contao/templates/modules');
        TemplateLoader::addFile('mod_article_foo', 'article-bundle/contao/templates/modules');
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

        TemplateLoader::addFile('ctlg_view', 'catalog-manager/contao/templates');
        TemplateLoader::addFile('ctlg_view_details', 'catalog-manager/contao/templates');

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
     */
    public function testSupportsHyphensInCustomTemplateNames(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.9: Using hyphens in the template name "mod_article-custom.html5" has been deprecated %s.');

        (new Filesystem())->touch([
            Path::join($this->getTempDir(), '/templates/mod_article-custom.html5'),
            Path::join($this->getTempDir(), '/templates/mod_article_custom.html5'),
        ]);

        TemplateLoader::addFile('mod_article', 'core-bundle/contao/templates/modules');

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

    /**
     * @group legacy
     */
    public function testReturnsACustomTwigTemplate(): void
    {
        $templateHierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $templateHierarchy
            ->method('getInheritanceChains')
            ->willReturn([
                'mod_article' => ['some/path/mod_article.html.twig' => '@Contao_Global/mod_article.html.twig'],
                'mod_foo' => ['some/path/mod_foo.html.twig' => '@Contao_Global/mod_foo.html.twig'],
                'mod_article_custom' => ['some/path/mod_article_custom.html.twig' => '@Contao_Global/mod_article_custom.html.twig'],
            ])
        ;

        System::getContainer()->set('contao.twig.filesystem_loader', $templateHierarchy);

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

    public function testThrowsExceptionWhenProvidedWithAModernFragmentTemplate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Using Contao\Controller::getTemplateGroup() with modern fragment templates is not supported. Use the "contao.twig.finder_factory" service instead.');

        Controller::getTemplateGroup('content_element/text');
    }

    public function testThrowsExceptionWhenProvidedWithAModernFragmentTemplateAsPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Using Contao\Controller::getTemplateGroup() with modern fragment templates is not supported. Use the "contao.twig.finder_factory" service instead.');

        Controller::getTemplateGroup('content_element', [], 'content_element/text');
    }
}
