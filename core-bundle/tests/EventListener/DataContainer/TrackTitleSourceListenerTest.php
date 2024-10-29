<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\TrackTitleSourceListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\Controller\ContentElement\ContentElementTestCase;
use Contao\DataContainer;
use Contao\Message;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

class TrackTitleSourceListenerTest extends ContentElementTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG']);

        parent::tearDown();
    }

    public function testTrackFilesHaveInvalidMetadata(): void
    {
        $uuids = [
            self::FILE_SUBTITLES_EN_VTT,
            self::FILE_SUBTITLES_INVALID_VTT,
        ];

        $GLOBALS['TL_LANG']['ERR']['textTrackMetadataMissing'] = 'Message: %s';

        $message = \sprintf($GLOBALS['TL_LANG']['ERR']['textTrackMetadataMissing'], 'subtitles-incomplete.vtt');

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag
            ->expects($this->once())
            ->method('add')
            ->with('contao.BE.error', $message)
        ;

        $adapter = $this->mockAdapter(['addError']);
        $adapter
            ->method('addError')
            ->willReturnCallback(
                static function ($message) use ($flashBag): void {
                    $flashBag->add('contao.BE.error', $message);
                },
            )
        ;

        $dataContainer = $this->createMock(DataContainer::class);
        $framework = $this->mockContaoFramework([Message::class => $adapter]);

        $listener = $this->getListener($framework);
        $listener($uuids, $dataContainer);
    }

    public function testTrackFilesHaveValidMetadata(): void
    {
        $uuids = [
            self::FILE_SUBTITLES_DE_VTT,
            self::FILE_SUBTITLES_EN_VTT,
        ];

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag
            ->method('has')
            ->with('contao.BE.error')
            ->willReturn(false)
        ;

        $flashBag->add('contao.BE.error', 'ERROR');

        $adapter = $this->mockAdapter(['hasError']);
        $adapter
            ->method('hasError')
            ->willReturn(false)
        ;

        $dataContainer = $this->createMock(DataContainer::class);
        $framework = $this->mockContaoFramework([Message::class => $adapter]);

        $listener = $this->getListener($framework);
        $value = $listener($uuids, $dataContainer);

        $this->assertSame($uuids, $value);
    }

    public function testResetErrorMessageOnValidMetaData(): void
    {
        $uuids = [
            self::FILE_SUBTITLES_DE_VTT,
            self::FILE_SUBTITLES_EN_VTT,
        ];

        $adapter = $this->mockAdapter(['hasError', 'reset']);
        $adapter
            ->method('hasError')
            ->willReturn(true)
        ;

        $framework = $this->mockContaoFramework([Message::class => $adapter]);

        $dataContainer = $this->createMock(DataContainer::class);
        $flashBag = $this->createMock(FlashBagAwareSessionInterface::class);

        $request = new Request();
        $request->setSession($flashBag);

        $listener = $this->getListener($framework);
        $value = $listener($uuids, $dataContainer);

        $this->assertSame($uuids, $value);
    }

    private function getListener(ContaoFramework $framework): TrackTitleSourceListener
    {
        return new TrackTitleSourceListener($framework, $this->getDefaultStorage());
    }
}
