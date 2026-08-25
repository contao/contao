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
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\TestCase\ContaoTestCase;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\TwigRenderer;
use Knp\Menu\Twig\MenuExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Runtime\EscaperRuntime;
use Twig\TwigFunction;

class BackendJobsListenerTest extends ContaoTestCase
{
    public function testAddsTheJobsButton(): void
    {
        $params = [
            'do' => 'jobs',
        ];

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend', $params)
            ->willReturn('/contao?do=jobs')
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
            ->willReturn($this->createStub(BackendUser::class))
        ;

        $jobs = $this->createStub(Jobs::class);

        $listener = new BackendJobsListener($security, $router, $jobs);
        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(3, $children);
        $this->assertSame(['submenu', 'burger', 'jobs'], array_keys($children));

        $jobs = $children['jobs'];

        $this->assertSame('MSC.jobs', $jobs->getLabel());
        $this->assertSame('/contao?do=jobs', $jobs->getUri());
        $this->assertSame([BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE => '@Contao/backend/jobs/menu_item.html.twig', 'has_pending_jobs' => false, 'translation_domain' => 'contao_default'], $jobs->getExtras());

        $html = $this->createRenderer()->render($menu);
        $this->assertStringContainsString('class="icon-jobs"', $html);
        $this->assertStringContainsString('data-controller="contao--jobs"', $html);
        $this->assertStringContainsString('href="/contao?do=jobs"', $html);
    }

    private function createRenderer(): TwigRenderer
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../core-bundle/contao/templates', 'Contao');

        $bundlePath = new KnpMenuBundle()->getPath();
        $loader->addPath($bundlePath.'/templates', 'KnpMenu');
        $loader->addPath(\dirname($bundlePath).'/knp-menu/src/Knp/Menu/Resources/views');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => $id)
        ;

        $twig = new Environment($loader);
        $twig->addExtension(new MenuExtension());
        $twig->addExtension(new TranslationExtension($translator));
        $twig->addFunction(new TwigFunction('path', static fn (): string => '/jobs/pending'));
        $twig->addFunction(new TwigFunction('attrs', static fn (HtmlAttributes|iterable|string|null $attributes = null): HtmlAttributes => new HtmlAttributes($attributes)));
        $twig->getRuntime(EscaperRuntime::class)->addSafeClass(HtmlAttributes::class, ['html']);

        return new TwigRenderer($twig, '@Contao/backend/menu/_header.html.twig', new Matcher());
    }
}
