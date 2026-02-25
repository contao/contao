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
use Contao\FrontendTemplate;
use Contao\MemberModel;
use Contao\OptInModel;
use Contao\PageModel;
use Contao\System;
use Contao\Versions;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('getOptInTokenAndMemberData')]
    public function testOptInTokenAndMemberVariants(bool $isTokenValid, bool $isTokenConfirmed, bool $hasMember, array $optInData, string $email, int $expected,): void
    {
        $member = $this->createClassWithPropertiesStub(MemberModel::class);
        $member->id = 1;
        $member->email = 'email@example.com';

        $container = $this->getContainerWithFrameworkTemplate($member);
        $container->set('contao.framework', $this->mockFrameworkWithTemplate($hasMember ? $member : null));

        $optInToken = $this->createMock(OptInToken::class);
        $optInToken
            ->expects($this->once())
            ->method('isValid')
            ->willReturn($isTokenValid)
        ;

        $optInToken
            ->expects($isTokenValid ? $this->once() : $this->never())
            ->method('getRelatedRecords')
            ->willReturn($optInData)
        ;

        $optInToken
            ->expects($isTokenValid && $hasMember ? $this->once() : $this->never())
            ->method('isConfirmed')
            ->willReturn($isTokenConfirmed)
        ;

        $optInToken
            ->expects('' !== $email ? $this->once() : $this->never())
            ->method('getEmail')
            ->willReturn($email)
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

        $this->assertSame($expected, $response->getStatusCode());
    }

    public static function getOptInTokenAndMemberData(): iterable
    {
        yield 'Return when token is invalid' => [
            false, false, false, [], '', Response::HTTP_OK,
        ];

        yield 'Return when there are no related records' => [
            true, false, false, [], '', Response::HTTP_OK,
        ];

        yield 'Return when the user ID is missing' => [
            true, false, false, ['tl_member' => []], '', Response::HTTP_OK,
        ];

        yield 'Return when the user is null' => [
            true, false, false, ['tl_member' => [1]], '', Response::HTTP_OK,
        ];

        yield 'Return when the token is already confirmed' => [
            true, true, true, ['tl_member' => [1]], '', Response::HTTP_OK,
        ];

        yield 'Return when the token email is not the same as the user email' => [
            true, false, true, ['tl_member' => [1]], 'foo@bar.com', Response::HTTP_OK,
        ];

        yield 'Return when form is not valid' => [
            true, false, true, ['tl_member' => [1]], 'email@example.com', Response::HTTP_OK,
        ];
    }

    public function testUpdatesPassword(): void
    {
        $member = $this->createClassWithPropertiesStub(MemberModel::class);
        $member->id = 1;
        $member->email = 'email@example.com';

        $container = $this->getContainerWithFrameworkTemplate($member);
        $container->set('contao.framework', $this->mockFrameworkWithTemplate($member, false, true));

        $lostPasswordForm = $this->createMock(FormInterface::class);
        $lostPasswordForm
            ->expects($this->once())
            ->method('handleRequest')
        ;

        $lostPasswordForm
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(false)
        ;

        $changePasswordForm = $this->createMock(FormInterface::class);
        $changePasswordForm
            ->expects($this->once())
            ->method('handleRequest')
        ;

        $changePasswordForm
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true)
        ;

        $changePasswordForm
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true)
        ;

        $formField = $this->createMock(FormInterface::class);
        $formField
            ->expects($this->exactly(2))
            ->method('getData')
            ->willReturn('foobar')
        ;

        $changePasswordForm
            ->expects($this->exactly(2))
            ->method('get')
            ->with('newpassword')
            ->willReturn($formField)
        ;

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($lostPasswordForm, $changePasswordForm)
        ;

        $container->set('form.factory', $formFactory);

        $optInToken = $this->createMock(OptInToken::class);
        $optInToken
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true)
        ;

        $optInToken
            ->expects($this->once())
            ->method('getRelatedRecords')
            ->willReturn(['tl_member' => [1]])
        ;

        $optInToken
            ->expects($this->once())
            ->method('isConfirmed')
            ->willReturn(false)
        ;

        $optInToken
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('email@example.com')
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
                ->expects($this->atLeastOnce())
                ->method('create')
                ->willReturn($form)
            ;
        }

        return $formFactory;
    }

    private function mockFrameworkWithTemplate(MemberModel|null $member = null, bool $hasCallback = false, bool $hasVersions = false): ContaoFramework|MockObject|Stub
    {
        $memberAdapter = $this->createAdapterStub(['findActiveByEmailAndUsername', 'findById']);
        $memberAdapter
            ->method('findActiveByEmailAndUsername')
            ->willReturn($member)
        ;

        $memberAdapter
            ->method('findById')
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

        $optInAdapter = $this->createAdapterStub(['findUnconfirmedByRelatedTableAndId']);
        $optInAdapter
            ->method('findUnconfirmedByRelatedTableAndId')
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkStub([
            MemberModel::class => $memberAdapter,
            Controller::class => $this->createAdapterStub(['loadDataContainer']),
            System::class => $systemAdapter,
            PageModel::class => $pageAdapter,
            OptInModel::class => $optInAdapter,
        ]);

        $versions = $this->createStub(Versions::class);

        if ($hasVersions) {
            $versions = $this->createMock(Versions::class);
            $versions
                ->expects($this->once())
                ->method('setUsername')
            ;

            $versions
                ->expects($this->once())
                ->method('setEditUrl')
            ;

            $versions
                ->expects($this->once())
                ->method('initialize')
            ;
        }

        $template = $this->createStub(FrontendTemplate::class);
        $template
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        $framework
            ->method('createInstance')
            ->willReturnMap([[FrontendTemplate::class, ['ce_lost_password'], $template], [Versions::class, ['tl_member', 1], $versions]])
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
