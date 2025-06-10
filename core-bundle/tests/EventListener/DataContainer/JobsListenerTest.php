<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\JobsListener;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class JobsListenerTest extends TestCase
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
        );
        $listener();

        $this->assertNull($GLOBALS['TL_DCA']['tl_job'] ?? null);
    }

    public function testInvokeWithoutUser(): void
    {
        $listener = new JobsListener(
            $this->mockSecurity(),
            $this->mockConnection(),
            $this->getRequestStack(Request::create('/')),
        );
        $listener();

        $this->assertNull($GLOBALS['TL_DCA']['tl_job'] ?? null);
    }

    public function testRegularView(): void
    {
        $listener = new JobsListener(
            $this->mockSecurity('username'),
            $this->mockConnection(),
            $this->getRequestStack(Request::create('/contao?do=jobs')),
        );
        $listener();

        $this->assertSame(
            [
                'list' => [
                    'sorting' => [
                        'filter' => [
                            "pid = 0 AND (owner = 'username' OR (public = '1' AND owner = 'SYSTEM'))",
                        ],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_job'],
        );
    }

    public function testChildView(): void
    {
        $listener = new JobsListener(
            $this->mockSecurity('username'),
            $this->mockConnection(),
            $this->getRequestStack(Request::create('/contao?do=jobs&ptable=tl_job')),
        );
        $listener();

        $this->assertSame(
            [
                'list' => [
                    'sorting' => [
                        'mode' => DataContainer::MODE_PARENT,
                        'filter' => [
                            "pid != 0 AND (owner = 'username' OR (public = '1' AND owner = 'SYSTEM'))",
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

    private function mockSecurity(string|null $username = null): Security
    {
        $userMock = $this->createMock(UserInterface::class);
        $userMock
            ->expects($username ? $this->atLeastOnce() : $this->never())
            ->method('getUserIdentifier')
            ->willReturn($username ?? '') // Cannot return null because that's not allowed but $this->never() asserts that '' is never returned
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($username ? $userMock : null)
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
