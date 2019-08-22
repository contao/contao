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

        TemplateLoader::addFile('mod_article', 'src/Resources/contao/templates/modules');
        TemplateLoader::addFile('mod_article_list', 'src/Resources/contao/templates/modules');
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

    public function testReturnsAllTemplatesOfAGroup(): void
    {
        $this->assertSame(
            [
                'mod_article' => 'mod_article',
                'mod_article_custom' => 'mod_article_custom (global)',
                'mod_article_list' => 'mod_article_list',
                'mod_article_list_custom' => 'mod_article_list_custom (global)',
            ],
            Controller::getTemplateGroup('mod_')
        );
    }

    public function testReturnsTheCustomTemplatesForAGivenTemplate(): void
    {
        $this->assertSame(
            [
                'mod_article_custom' => 'mod_article_custom (global)',
            ],
            Controller::getTemplateGroup('mod_article_')
        );

        $this->assertSame(
            [
                'mod_article_list_custom' => 'mod_article_list_custom (global)',
            ],
            Controller::getTemplateGroup('mod_article_list_')
        );
    }

    public function testIncludesTheTempateItselfIfThereIsNoTrailingUnderscore(): void
    {
        $this->assertSame(
            [
                'mod_article_list' => 'mod_article_list',
                'mod_article_list_custom' => 'mod_article_list_custom (global)',
            ],
            Controller::getTemplateGroup('mod_article_list')
        );
    }
}
