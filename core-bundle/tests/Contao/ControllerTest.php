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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ControllerTest extends TestCase
{
    public function testReturnsTheTimeZones(): void
    {
        $timeZones = System::getTimeZones();

        $this->assertCount(9, $timeZones['General']);
        $this->assertCount(51, $timeZones['Africa']);
        $this->assertCount(140, $timeZones['America']);
        $this->assertCount(10, $timeZones['Antarctica']);
        $this->assertCount(83, $timeZones['Asia']);
        $this->assertCount(11, $timeZones['Atlantic']);
        $this->assertCount(22, $timeZones['Australia']);
        $this->assertCount(4, $timeZones['Brazil']);
        $this->assertCount(9, $timeZones['Canada']);
        $this->assertCount(2, $timeZones['Chile']);
        $this->assertCount(53, $timeZones['Europe']);
        $this->assertCount(11, $timeZones['Indian']);
        $this->assertCount(4, $timeZones['Brazil']);
        $this->assertCount(3, $timeZones['Mexico']);
        $this->assertCount(40, $timeZones['Pacific']);
        $this->assertCount(13, $timeZones['United States']);
    }

    public function testGeneratesTheMargin(): void
    {
        $margins = [
            'top' => '40px',
            'right' => '10%',
            'bottom' => '-2px',
            'left' => '-50%',
            'unit' => '',
        ];

        $this->assertSame('margin:40px 10% -2px -50%;', Controller::generateMargin($margins));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddToUrlWithoutQueryString(): void
    {
        \define('TL_SCRIPT', '');

        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'cri');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('request_stack', $requestStack);

        System::setContainer($container);

        $this->assertSame('', Controller::addToUrl(''));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page'));
        $this->assertSame('?do=page&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo'));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar'));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&id=2'));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2'));
        $this->assertSame('?act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20'));

        $this->assertSame('', Controller::addToUrl('', false));
        $this->assertSame('?do=page', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;ref=bar', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?act=edit&amp;id=2', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?act=edit&amp;id=2', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));

        $request->query->set('ref', 'ref');

        $this->assertSame('?ref=cri', Controller::addToUrl('', false));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddToUrlWithQueryString(): void
    {
        \define('TL_SCRIPT', '');

        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'cri');
        $request->server->set('QUERY_STRING', 'do=page&id=4');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('request_stack', $requestStack);

        System::setContainer($container);

        $this->assertSame('?do=page&amp;id=4', Controller::addToUrl(''));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page'));
        $this->assertSame('?do=page&amp;id=4&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo'));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar'));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&id=2'));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2'));
        $this->assertSame('?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20'));
        $this->assertSame('?do=page&amp;key=foo&amp;ref=cri', Controller::addToUrl('key=foo', true, ['id']));

        $this->assertSame('?do=page&amp;id=4', Controller::addToUrl('', false));
        $this->assertSame('?do=page&amp;id=4', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;id=4&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;id=4&amp;ref=bar', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
        $this->assertSame('?do=page&amp;key=foo', Controller::addToUrl('key=foo', false, ['id']));

        $request->query->set('ref', 'ref');

        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('', false));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;id=4&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
        $this->assertSame('?do=page&amp;key=foo&amp;ref=cri', Controller::addToUrl('key=foo', true, ['id']));
    }
}
