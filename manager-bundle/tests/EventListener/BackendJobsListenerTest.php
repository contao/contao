<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\EventListener;

use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\EventListener\Menu\BackendJobsListener;
use Contao\CoreBundle\Job\Jobs;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\MenuFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class BackendJobsListenerTest extends ContaoTestCase
{
    public function testAddsTheJobsButton(): void
    {
        $request = new Request();
        $request->server->set('QUERY_STRING', 'do=page');
        $request->attributes->set('_contao_referer_id', 'foo');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $params = [
            'do' => 'jobs',
            'ref' => 'foo',
        ];

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend', $params)
            ->willReturn('/contao?do=jobs&ref=foo')
        ;

        $factory = new MenuFactory();

        $menu = $factory->createItem('headerMenu');
        $menu->addChild($factory->createItem('submenu'));
        $menu->addChild($factory->createItem('burger'));

        $event = new MenuEvent($factory, $menu);
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(BackendUser::class))
        ;
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/jobs/_menu_item.html.twig',
                [
                    'jobs_link' => '/contao?do=jobs&ref=foo',
                    'has_pending_jobs' => false,
                ],
            )
            ->willReturn('<twig html>')
        ;
        $jobs = $this->createMock(Jobs::class);

        $listener = new BackendJobsListener($security, $twig, $router, $requestStack, $jobs);
        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(3, $children);
        $this->assertSame(['submenu', 'burger', 'jobs'], array_keys($children));

        $jobs = $children['jobs'];

        $this->assertSame('<twig html>', $jobs->getLabel());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $jobs->getExtras());
    }
}
