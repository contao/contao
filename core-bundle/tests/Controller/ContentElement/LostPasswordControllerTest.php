<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\ContentElement;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Controller\ContentElement\LostPasswordController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\OptIn\OptInToken;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\MemberModel;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LostPasswordControllerTest extends ContentElementTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA']);
    }

    public function testExecutesOnloadCallbacks(): void
    {
        $member = $this->createClassWithPropertiesStub(MemberModel::class);

        $container = $this->getContainerWithFrameworkTemplate($member);
        $container->set('contao.framework', $this->mockFrameworkWithTemplate($member, true));

        $controller = new LostPasswordController(
            $this->createStub(TranslatorInterface::class),
            $this->createStub(RateLimiterFactoryInterface::class),
            $this->createStub(OptIn::class),
            $this->createStub(SimpleTokenParser::class),
            $this->createStub(LoggerInterface::class),
        );
        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $request = new Request();

        $GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] = [
            ['Test\Callback', 'callback'],
            static function (): void {},
        ];

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRateLimitForSendPasswordLink(): void
    {
        $member = $this->createClassWithPropertiesStub(MemberModel::class);

        $container = $this->getContainerWithFrameworkTemplate($member, true);
        $container->set('contao.framework', $this->mockFrameworkWithTemplate($member));

        $controller = new LostPasswordController(
            $this->createStub(TranslatorInterface::class),
            $this->createStub(RateLimiterFactoryInterface::class),
            $this->createStub(OptIn::class),
            $this->createStub(SimpleTokenParser::class),
            $this->createStub(LoggerInterface::class),
        );
        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testSendPasswordLink(): void
    {
        $member = $this->createClassWithPropertiesStub(MemberModel::class, [
            'id' => 1,
            'email' => 'email@example.com',
        ]);

        $page = $this->createClassWithPropertiesStub(PageModel::class);

        $container = $this->getContainerWithFrameworkTemplate($member, true, $page, '/foobar');
        $container->set('contao.framework', $this->mockFrameworkWithTemplate($member));

        $rateLimit = $this->createMock(RateLimit::class);
        $rateLimit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(true)
        ;

        $rateLimiter = $this->createMock(LimiterInterface::class);
        $rateLimiter
            ->expects($this->once())
            ->method('consume')
            ->willReturn($rateLimit)
        ;

        $rateLimiterFactory = $this->createMock(RateLimiterFactoryInterface::class);
        $rateLimiterFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($rateLimiter)
        ;

        $simpleTokenParser = $this->createMock(SimpleTokenParser::class);
        $simpleTokenParser
            ->expects($this->once())
            ->method('parse')
        ;

        $controller = new LostPasswordController(
            $this->createStub(TranslatorInterface::class),
            $rateLimiterFactory,
            $this->createStub(OptIn::class),
            $simpleTokenParser,
            $this->createStub(LoggerInterface::class),
        );
        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class, [
            'reg_password' => 'Some text',
        ]);

        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testReturnsWithInvalidToken(): void
    {
        $member = $this->createClassWithPropertiesStub(MemberModel::class);

        $container = $this->getContainerWithFrameworkTemplate($member);
        $container->set('contao.framework', $this->mockFrameworkWithTemplate($member));

        $optInToken = $this->createMock(OptInToken::class);
        $optInToken
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(false)
        ;

        $optInToken
            ->expects($this->never())
            ->method('getRelatedRecords')
        ;

        $optIn = $this->createMock(OptIn::class);
        $optIn
            ->expects($this->once())
            ->method('find')
            ->willReturn($optInToken)
        ;

        $controller = new LostPasswordController(
            $this->createStub(TranslatorInterface::class),
            $this->createStub(RateLimiterFactoryInterface::class),
            $optIn,
            $this->createStub(SimpleTokenParser::class),
            $this->createStub(LoggerInterface::class),
        );
        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $request = new Request();
        $request->query->set('token', 'pw-notasecrettoken');

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @template T
     *
     * @param FormInterface<T>|null $form
     */
    private function mockFormFactory(FormInterface|null $form = null): FormFactoryInterface
    {
        $formFactory = $this->createStub(FormFactoryInterface::class);

        if ($form) {
            $formFactory = $this->createMock(FormFactoryInterface::class);
            $formFactory
                ->expects($this->once())
                ->method('create')
                ->willReturn($form)
            ;
        }

        return $formFactory;
    }

    private function mockFrameworkWithTemplate(MemberModel|null $member = null, bool $hasCallback = false): ContaoFramework|MockObject|Stub
    {
        $template = new FragmentTemplate('lost_password', static fn () => new Response());

        $memberAdapter = $this->createAdapterStub(['findActiveByEmailAndUsername']);
        $memberAdapter
            ->method('findActiveByEmailAndUsername')
            ->willReturn($member)
        ;

        $systemAdapter = $this->createAdapterStub(['importStatic']);

        if ($hasCallback) {
            $onloadCallback = $this->createAdapterMock(['callback']);
            $onloadCallback
                ->expects($this->once())
                ->method('callback')
            ;

            $systemAdapter = $this->createAdapterMock(['importStatic']);
            $systemAdapter
                ->expects($this->once())
                ->method('importStatic')
                ->with('Test\Callback')
                ->willReturn($onloadCallback)
            ;
        }

        $pageAdapter = $this->createAdapterStub(['findById']);
        $pageAdapter
            ->method('findById')
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkStub([
            MemberModel::class => $memberAdapter,
            Controller::class => $this->createAdapterStub(['loadDataContainer']),
            System::class => $systemAdapter,
            PageModel::class => $pageAdapter,
        ]);

        $framework
            ->method('createInstance')
            ->willReturn($template)
        ;

        return $framework;
    }

    private function getContainerWithFrameworkTemplate(MemberModel|null $member = null, bool|null $formIsValid = null, PageModel|null $page = null, string|null $redirectUrl = null): ContainerBuilder
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(null !== $formIsValid ? $this->once() : $this->never())
            ->method('handleRequest')
        ;

        $form
            ->expects(null !== $formIsValid ? $this->once() : $this->never())
            ->method('isSubmitted')
            ->willReturn(true)
        ;

        $form
            ->expects(null !== $formIsValid ? $this->once() : $this->never())
            ->method('isValid')
            ->willReturn((bool) $formIsValid)
        ;

        $formField = $this->createMock(FormInterface::class);
        $formField
            ->expects($formIsValid ? $this->exactly(2) : $this->never())
            ->method('getData')
            ->willReturn('12345678')
        ;

        $form
            ->expects($formIsValid ? $this->exactly(2) : $this->never())
            ->method('get')
            ->willReturn($formField)
        ;

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($page ? $this->once() : $this->never())
            ->method('getCurrentPage')
            ->willReturn($page)
        ;

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($redirectUrl ? $this->atLeast(1) : $this->never())
            ->method('generate')
            ->willReturn((string) $redirectUrl)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockFrameworkWithTemplate($member));
        $container->set('contao.routing.page_finder', $pageFinder);
        $container->set('contao.routing.content_url_generator', $contentUrlGenerator);
        $container->set('contao.cache.tag_manager', $this->createStub(CacheTagManager::class));
        $container->set('form.factory', $this->mockFormFactory(null !== $formIsValid ? $form : null));
        $container->set('security.password_hasher_factory', $this->createStub(PasswordHasherFactoryInterface::class));
        $container->set('event_dispatcher', $this->createStub(EventDispatcherInterface::class));
        $container->set('router', $this->createStub(RouterInterface::class));

        System::setContainer($container);

        return $container;
    }
}
