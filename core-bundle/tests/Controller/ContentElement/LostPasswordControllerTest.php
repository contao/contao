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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\MemberModel;
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

        $framework = $this->createContaoFrameworkStub([
            MemberModel::class => $memberAdapter,
            Controller::class => $this->createAdapterStub(['loadDataContainer']),
            System::class => $systemAdapter,
        ]);

        $framework
            ->method('createInstance')
            ->willReturn($template)
        ;

        return $framework;
    }

    private function getContainerWithFrameworkTemplate(MemberModel|null $member = null, bool|null $formIsValid = null): ContainerBuilder
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

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockFrameworkWithTemplate($member));
        $container->set('contao.routing.content_url_generator', $this->createStub(ContentUrlGenerator::class));
        $container->set('contao.cache.tag_manager', $this->createStub(CacheTagManager::class));
        $container->set('form.factory', $this->mockFormFactory(null !== $formIsValid ? $form : null));
        $container->set('security.password_hasher_factory', $this->createStub(PasswordHasherFactoryInterface::class));
        $container->set('event_dispatcher', $this->createStub(EventDispatcherInterface::class));
        $container->set('router', $this->createStub(RouterInterface::class));

        System::setContainer($container);

        return $container;
    }
}
