<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\DataContainer\JobsListener;
use Contao\DataContainer;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class JobsListenerTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testInvokeWithoutRequest(): void
    {
        $listener = new JobsListener(
            $this->createMock(Security::class),
            $this->mockConnection(),
            $this->getRequestStack(),
            $this->mockContaoFramework(),
        );
        $listener->onLoadCallback();

        $this->assertNull($GLOBALS['TL_DCA']['tl_job'] ?? null);
    }

    public function testInvokeWithoutUser(): void
    {
        $listener = new JobsListener(
            $this->mockSecurity(),
            $this->mockConnection(),
            $this->getRequestStack(Request::create('/')),
            $this->mockContaoFramework(),
        );
        $listener->onLoadCallback();

        $this->assertNull($GLOBALS['TL_DCA']['tl_job'] ?? null);
    }

    public function testRegularView(): void
    {
        $framework = $this->mockContaoFramework([System::class => $this->mockAdapter(['loadLanguageFile'])]);

        $listener = new JobsListener(
            $this->mockSecurity(42),
            $this->mockConnection(),
            $this->getRequestStack(Request::create('/contao?do=jobs')),
            $framework,
        );
        $listener->onLoadCallback();

        $this->assertSame(
            [
                'list' => [
                    'sorting' => [
                        'filter' => [
                            "pid = 0 AND (owner = 42 OR (public = '1' AND owner = 0))",
                        ],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_job'],
        );
    }

    public function testChildView(): void
    {
        $framework = $this->mockContaoFramework([System::class => $this->mockAdapter(['loadLanguageFile'])]);

        $listener = new JobsListener(
            $this->mockSecurity(42),
            $this->mockConnection(),
            $this->getRequestStack(Request::create('/contao?do=jobs&ptable=tl_job')),
            $framework,
        );
        $listener->onLoadCallback();

        $this->assertSame(
            [
                'list' => [
                    'sorting' => [
                        'mode' => DataContainer::MODE_PARENT,
                        'filter' => [
                            "pid != 0 AND (owner = 42 OR (public = '1' AND owner = 0))",
                        ],
                    ],
                    'label' => [
                        'fields' => [
                            'uuid',
                            'status',
                        ],
                        'format' => '%s <span class="label-info">%s</span>',
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_job'],
        );
    }

    private function getRequestStack(Request|null $request = null): RequestStack
    {
        $requestStack = new RequestStack();

        if ($request) {
            $requestStack->push($request);
        }

        return $requestStack;
    }

    private function mockSecurity(int|null $userId = null): Security
    {
        $userMock = $this->mockClassWithProperties(BackendUser::class, ['id' => $userId]);
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($userId ? $userMock : null)
        ;

        return $security;
    }

    private function mockConnection(): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quote')
            ->willReturnCallback(static fn ($value): string => "'".$value."'")
        ;

        return $connection;
    }
}
