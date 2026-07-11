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

use Contao\CoreBundle\Controller\ContentElement\PlayerController;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\PublicUri\Options;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Nyholm\Psr7\Uri;
use Symfony\Component\Uid\Uuid;

class PlayerControllerTest extends ContentElementTestCase
{
    public function testOutputsFigure(): void
    {
        $storage = $this->createStub(VirtualFilesystem::class);
        $storage
            ->method('getPrefix')
            ->willReturn('files')
        ;

        $storage
            ->method('get')
            ->willReturnCallback(
                static function (Uuid $uuid): FilesystemItem|null {
                    $storageMap = [
                        self::FILE_VIDEO_MP4 => new FilesystemItem(true, 'video.mp4', null, null, 'video/mp4'),
                        self::FILE_VIDEO_OGV => new FilesystemItem(true, 'video.ogv', null, null, 'video/ogg'),
                    ];

                    return $storageMap[$uuid->toRfc4122()] ?? null;
                },
            )
        ;

        $storage
            ->method('generatePublicUri')
            ->willReturnCallback(
                function (string $path, Options|null $options = null): Uri|null {
                    $this->assertNotNull($options);
                    $this->assertNotNull($options->get(Options::OPTION_TEMPORARY_ACCESS_INFORMATION));

                    $publicUriMap = [
                        'video.mp4' => new Uri('https://example.com/files/video.mp4'),
                        'video.ogv' => new Uri('https://example.com/files/video.ogv'),
                    ];

                    return $publicUriMap[$path] ?? null;
                },
            )
        ;

        $response = $this->renderWithModelData(
            new PlayerController($storage),
            [
                'type' => 'player',
                'playerSRC' => serialize([
                    self::FILE_VIDEO_MP4,
                    self::FILE_VIDEO_OGV,
                ]),
                'playerOptions' => serialize([
                    'player_autoplay', 'player_loop',
                ]),
                'playerCaption' => 'Caption',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-player">
                <figure>
                    <video controls autoplay loop>
                        <source type="video/mp4" src="https://example.com/files/video.mp4">
                        <source type="video/ogg" src="https://example.com/files/video.ogv">
                    </video>
                    <figcaption>Caption</figcaption>
                </figure>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsEmptyResponse(): void
    {
        $response = $this->renderWithModelData(
            new PlayerController($this->getDefaultStorage()),
            [
                'type' => 'player',
            ],
        );

        $this->assertEmpty($response->getContent());
    }

    public function testOutputsSummary(): void
    {
        $response = $this->renderWithModelData(
            new PlayerController($this->getDefaultStorage()),
            [
                'type' => 'player',
                'playerSRC' => serialize([
                    self::FILE_VIDEO_MP4,
                    self::FILE_VIDEO_OGV,
                ]),
            ],
            asEditorView: true,
        );

        $expectedOutput = <<<'HTML'
            <div class="content-player">
                <ul>
                    <li>
                        <span>video.mp4</span> <span class="size">(0.0 Byte)</span>
                    </li>
                    <li>
                        <span>video.ogv</span> <span class="size">(0.0 Byte)</span>
                    </li>
                </ul>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsTextTrack(): void
    {
        $response = $this->renderWithModelData(
            new PlayerController($this->getDefaultStorage()),
            [
                'type' => 'player',
                'playerSRC' => serialize([
                    self::FILE_VIDEO_MP4,
                ]),
                'textTrackSRC' => serialize([
                    self::FILE_SUBTITLES_EN_VTT,
                    self::FILE_SUBTITLES_DE_VTT,
                ]),
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-player">
                <figure>
                    <video controls>
                        <source type="video/mp4" src="https://example.com/files/video.mp4">
                        <track label="English" srclang="en" src="https://example.com/files/subtitles-en.vtt" default>
                        <track kind="captions" label="Deutsch" srclang="de" src="https://example.com/files/subtitles-de.vtt">
                    </video>
                </figure>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testEmptyTextTrackLabelsOrLanguages(): void
    {
        $response = $this->renderWithModelData(
            new PlayerController($this->getDefaultStorage()),
            [
                'type' => 'player',
                'playerSRC' => serialize([
                    self::FILE_VIDEO_MP4,
                ]),
                'textTrackSRC' => serialize([
                    self::FILE_SUBTITLES_INVALID_VTT,
                ]),
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-player">
                <figure>
                    <video controls>
                        <source type="video/mp4" src="https://example.com/files/video.mp4">
                    </video>
                </figure>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
