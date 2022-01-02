<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\RootPageDependentModuleListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RootPageDependentModuleListenerTest extends ContaoTestCase
{
    public function testReturnsIfTheRequestIsNotABackendRequest(): void
    {
        $request = new Request([], [], ['_scope' => 'frontend']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $listener = new RootPageDependentModuleListener(
            $this->createMock(Connection::class),
            $this->createMock(TranslatorInterface::class),
            $this->mockScopeMatcher(false, $request),
            $requestStack,
            $this->createMock(CsrfTokenManagerInterface::class),
            'contao_csrf_token'
        );

        $listener->onLoadDataContainer('tl_module');
    }

    public function testReturnsIfThereAreNoFields(): void
    {
        $request = new Request([], [], ['_scope' => 'backend']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $listener = new RootPageDependentModuleListener(
            $this->createMock(Connection::class),
            $this->createMock(TranslatorInterface::class),
            $this->mockScopeMatcher(true, $request),
            $requestStack,
            $this->createMock(CsrfTokenManagerInterface::class),
            'contao_csrf_token'
        );

        $this->populateGlobalsArray([]);

        $listener->onLoadDataContainer('tl_module');

        $this->unsetGlobalsArray();
    }

    public function testOnlyModifyFieldsWithProperInputType(): void
    {
        $request = new Request([], [], ['_scope' => 'backend']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $listener = new RootPageDependentModuleListener(
            $this->createMock(Connection::class),
            $this->createMock(TranslatorInterface::class),
            $this->mockScopeMatcher(true, $request),
            $requestStack,
            $this->createMock(CsrfTokenManagerInterface::class),
            'contao_csrf_token'
        );

        $this->populateGlobalsArray([
            'field1' => [
                'inputType' => 'rootPageDependentModule',
            ],
            'field2' => [
                'eval' => [],
            ],
            'field3' => [
                'inputType' => 'text',
            ],
        ]);

        $listener->onLoadDataContainer('tl_module');

        $this->assertSame([
            'field1' => [
                'inputType' => 'rootPageDependentModule',
                'eval' => [
                    'rootPages' => [],
                    'blankOptionLabel' => null,
                ]
            ],
            'field2' => [
                'eval' => [],
            ],
            'field3' => [
                'inputType' => 'text',
            ],
        ], $GLOBALS['TL_DCA']['tl_module']['fields']);

        $this->unsetGlobalsArray();
    }

    public function testAddRootPagesToFieldConfig(): void
    {
        $request = new Request([], [], ['_scope' => 'backend']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $result = $this->createMock(Result::class);
        $result
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['id' => 1, 'title' => 'title-1', 'language' => 'language-1'],
                ['id' => 2, 'title' => 'title-2', 'language' => 'language-2'],
                ['id' => 3, 'title' => 'title-3', 'language' => 'language-3'],
            ])
        ;

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($statement)
        ;

        $listener = new RootPageDependentModuleListener(
            $connection,
            $this->createMock(TranslatorInterface::class),
            $this->mockScopeMatcher(true, $request),
            $requestStack,
            $this->createMock(CsrfTokenManagerInterface::class),
            'contao_csrf_token'
        );

        $this->populateGlobalsArray([
            'field1' => [
                'inputType' => 'rootPageDependentModule',
            ],
        ]);

        $listener->onLoadDataContainer('tl_module');

        $this->assertSame([
            'field1' => [
                'inputType' => 'rootPageDependentModule',
                'eval' => [
                    'rootPages' => [
                        1 => 'title-1 (language-1)',
                        2 => 'title-2 (language-2)',
                        3 => 'title-3 (language-3)'
                    ],
                    'blankOptionLabel' => null,
                ]
            ],
        ], $GLOBALS['TL_DCA']['tl_module']['fields']);

        $this->unsetGlobalsArray();
    }

    private function populateGlobalsArray(array $data): void
    {
        $GLOBALS['TL_DCA']['tl_module']['fields'] = $data;
    }

    private function unsetGlobalsArray(): void
    {
        unset($GLOBALS['TL_DCA']['tl_module']['fields']);
    }

    private function getRequest(bool $withPageModel = false, PageModel $pageModel = null): Request
    {
        $request = new Request();
        $request->attributes->set('pageModel', null);

        $pageModel ??= $this->createMock(PageModel::class);

        if ($withPageModel) {
            $request->attributes->set('pageModel', $pageModel);
        }

        $request->setSession($this->createMock(SessionInterface::class));

        return $request;
    }

    /**
     * @return ScopeMatcher&MockObject
     */
    private function mockScopeMatcher(bool $hasBackendUser, Request $request): ScopeMatcher
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->with($request)
            ->willReturn($hasBackendUser)
        ;

        return $scopeMatcher;
    }
}
