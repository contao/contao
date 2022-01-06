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

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\DataContainer\PreviewLinkListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class PreviewLinkListenerTest extends TestCase
{
    public function testRemovesTheBackendModuleWithoutPreviewScript(): void
    {
        $GLOBALS['BE_MOD']['system'] = ['preview_link' => ['foo']];

        $listener = new PreviewLinkListener(
            $this->mockContaoFramework(),
            $this->createMock(Connection::class),
            $this->createMock(Security::class),
            $this->createMock(RequestStack::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(UriSigner::class),
            $this->createMock(TranslatorInterface::class),
            ''
        );

        $listener->unloadModuleWithoutPreviewScript();

        $this->assertSame([], $GLOBALS['BE_MOD']['system']);

        unset($GLOBALS['BE_MOD']);
    }

    public function testUnsetsTheDcaTableWithoutPreviewScript(): void
    {
        $GLOBALS['TL_DCA'] = ['tl_preview_link' => ['config' => ['foo']]];

        $listener = new PreviewLinkListener(
            $this->mockContaoFramework(),
            $this->createMock(Connection::class),
            $this->createMock(Security::class),
            $this->createMock(RequestStack::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(UriSigner::class),
            $this->createMock(TranslatorInterface::class),
            ''
        );

        $listener->unloadTableWithoutPreviewScript('tl_preview_link');

        $this->assertSame([], $GLOBALS['TL_DCA']);

        unset($GLOBALS['TL_DCA']);
    }

    public function testDoesNotUnloadOtherTables(): void
    {
        $GLOBALS['TL_DCA'] = ['tl_preview_link' => 'foo', 'tl_member' => 'bar'];

        $listener = new PreviewLinkListener(
            $this->mockContaoFramework(),
            $this->createMock(Connection::class),
            $this->createMock(Security::class),
            $this->createMock(RequestStack::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(UriSigner::class),
            $this->createMock(TranslatorInterface::class),
            ''
        );

        $listener->unloadTableWithoutPreviewScript('tl_member');

        $this->assertSame(['tl_preview_link' => 'foo', 'tl_member' => 'bar'], $GLOBALS['TL_DCA']);

        unset($GLOBALS['TL_DCA']);
    }

    /**
     * @dataProvider defaultDcaValueProvider
     */
    public function testSetsTheDefaultValueForDcaFields(string $url, bool $showUnpublished, int $userId): void
    {
        ClockMock::withClockMock(true);

        $GLOBALS['TL_DCA']['tl_preview_link'] = [
            'config' => ['notCreatable' => true],
            'fields' => [
                'url' => ['default' => ''],
                'showUnpublished' => ['default' => ''],
                'createdAt' => ['default' => 0],
                'expiresAt' => ['default' => 0],
                'createdBy' => ['default' => 0],
            ],
        ];

        $input = $this->mockClassWithProperties(Input::class, ['url' => $url, 'showUnpublished' => $showUnpublished]);
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => $userId]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $listener = new PreviewLinkListener(
            $this->mockContaoFramework([Input::class => $input]),
            $this->createMock(Connection::class),
            $security,
            $this->createMock(RequestStack::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(UriSigner::class),
            $this->createMock(TranslatorInterface::class),
            '/preview.php'
        );

        $dc = $this->mockClassWithProperties(DataContainer::class);

        $listener->createFromUrl($dc);

        $this->assertTrue($GLOBALS['TL_DCA']['tl_preview_link']['config']['notCreatable']);
        $this->assertSame($url, $GLOBALS['TL_DCA']['tl_preview_link']['fields']['url']['default']);
        $this->assertSame($showUnpublished ? '1' : '', $GLOBALS['TL_DCA']['tl_preview_link']['fields']['showUnpublished']['default']);
        $this->assertSame(ClockMock::time(), $GLOBALS['TL_DCA']['tl_preview_link']['fields']['createdAt']['default']);
        $this->assertSame(strtotime('+1 day', ClockMock::time()), $GLOBALS['TL_DCA']['tl_preview_link']['fields']['expiresAt']['default']);
        $this->assertSame($user, $GLOBALS['TL_DCA']['tl_preview_link']['fields']['createdBy']['default']);

        unset($GLOBALS['TL_DCA']);
        ClockMock::withClockMock(false);
    }

    public function defaultDcaValueProvider(): \Generator
    {
        yield [
            '/preview.php/foo/bar',
            true,
            1,
        ];

        yield [
            '/preview.php/foo/baz',
            false,
            2,
        ];
    }

    public function testUpdatesTheExpiresAtField(): void
    {
        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 42]);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with(
                'UPDATE tl_preview_link SET expiresAt=UNIX_TIMESTAMP(DATE_ADD(FROM_UNIXTIME(createdAt), INTERVAL expiresInDays DAY)) WHERE id=?',
                [$dc->id]
            )
        ;

        $listener = new PreviewLinkListener(
            $this->mockContaoFramework(),
            $connection,
            $this->createMock(Security::class),
            $this->createMock(RequestStack::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(UriSigner::class),
            $this->createMock(TranslatorInterface::class),
            ''
        );

        $listener->updateExpiresAt($dc);
    }
}
