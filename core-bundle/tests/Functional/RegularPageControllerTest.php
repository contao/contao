<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\CoreBundle\Controller\Page\RegularPageController;
use Contao\CoreBundle\Tests\Fixtures\Functional\PageController\TestFragmentController;
use Contao\PageModel;
use Contao\System;
use Contao\TestCase\FunctionalTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegularPageControllerTest extends FunctionalTestCase
{
    public function testRendersLayoutTemplate(): void
    {
        $container = self::createClient()->getContainer();
        System::setContainer($container);

        static::loadFixtures([__DIR__.'/../Fixtures/Functional/PageController/page-with-layout-and-content.yaml']);

        $page = PageModel::findOneByAlias('layout-test');
        $container->get('request_stack')->push(new Request(attributes: ['pageModel' => $page]));

        $GLOBALS['TL_CTE']['miscellaneous']['test'] = TestFragmentController::class;

        $response = $container->get(RegularPageController::class)($page);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $crawler = new Crawler($response->getContent());

        // Ensure article is rendered with the test fragment in it
        $articleNode = $crawler->filterXPath('//body//main//*[1]');

        $this->assertSame('article-1', $articleNode->attr('id'));
        $this->assertSame('mod_article block', $articleNode->attr('class'));
        $this->assertSame(
            '[content from test fragment controller for tl_content.1 in main]',
            $articleNode->innerText(),
        );

        // Ensure deferred rendering of additional head/body tags
        $this->assertCount(
            1,
            $crawler->filterXPath('//head//link[@href="test-fragment-styles.css"]'),
        );

        $this->assertCount(
            1,
            $crawler->filterXPath('//body//script[@id="test-fragment-script"]'),
        );
    }
}
