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

use Contao\Backend;
use Contao\CoreBundle\EventListener\DataContainer\CteAliasListener;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Security;

class CteAliasListenerTest extends TestCase
{
    public function testDisallowsDeletionOfReferencedElement(): void
    {
        $this->expectException(InternalServerErrorException::class);

        $request = new Request(['act' => 'delete', 'id' => 1]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockContaoFramework([
            Backend::class => $this->mockAdapter(['addToUrl']),
        ]);

        $listener = new CteAliasListener($requestStack, $this->createMock(Security::class), $this->mockConnection(), $framework);
        $listener->preserveReferenced();
    }

    public function testDoesNotDeleteReferencedElementWhenDeletingAll(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('CURRENT', ['IDS' => [1, 2]]);

        $request = new Request(['act' => 'deleteAll']);
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockContaoFramework([
            Backend::class => $this->mockAdapter(['addToUrl']),
        ]);

        $listener = new CteAliasListener($requestStack, $this->createMock(Security::class), $this->mockConnection(), $framework);
        $listener->preserveReferenced();

        $this->assertSame(array_values($session->all()['CURRENT']['IDS']), [2]);
    }

    public function testDoesNotShowDeleteButtonWithoutPermissions(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(false)
        ;

        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('delete_.svg')
            ->willReturn('delete_.svg')
        ;

        $framework = $this->mockContaoFramework([
            Image::class => $imageAdapter,
        ]);

        $listener = new CteAliasListener(new RequestStack(), $security, $this->createMock(Connection::class), $framework);
        $result = $listener->deleteElement(['type' => 'foo'], null, '', '', 'delete.svg', '');

        $this->assertSame('delete_.svg ', $result);
    }

    public function testDoesNotShowDeleteButtonForReferencedElement(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('delete_.svg')
            ->willReturn('delete_.svg')
        ;

        $framework = $this->mockContaoFramework([
            Image::class => $imageAdapter,
        ]);

        $listener = new CteAliasListener(new RequestStack(), $security, $this->mockConnection(), $framework);
        $result = $listener->deleteElement(['id' => 1, 'type' => 'foo'], null, '', '', 'delete.svg', '');

        $this->assertSame('delete_.svg ', $result);
    }

    public function testShowsDeleteButton(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('delete.svg')
            ->willReturn('delete.svg')
        ;

        $framework = $this->mockContaoFramework([
            Image::class => $imageAdapter,
            Backend::class => $this->mockAdapter(['addtoUrl']),
        ]);

        $listener = new CteAliasListener(new RequestStack(), $security, $this->mockConnection(), $framework);
        $result = $listener->deleteElement(['id' => 2, 'type' => 'foo'], null, '', '', 'delete.svg', '');

        $this->assertSame('<a href="" title="">delete.svg</a> ', $result);
    }

    /**
     * @return Connection&MockObject
     */
    private function mockConnection(): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllKeyValue')
            ->willReturn([1 => 1])
        ;

        return $connection;
    }
}
