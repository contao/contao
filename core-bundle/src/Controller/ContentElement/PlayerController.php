<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\FilesystemUtil;
use Contao\CoreBundle\Filesystem\SortMode;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\StringUtil;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-type FigureData array{
 *      media: array{
 *          type: 'video'|'audio',
 *          attributes: HtmlAttributes,
 *          sources: list<HtmlAttributes>,
 *      },
 *      metadata: Metadata
 *  }
 */
#[AsContentElement(category: 'media')]
class PlayerController extends AbstractContentElementController
{
    /**
     * @var array<string, UriInterface>
     */
    private array $publicUriByStoragePath = [];

    public function __construct(private readonly VirtualFilesystem $filesStorage)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        // Find and order source files
        $filesystemItems = FilesystemUtil::listContentsFromSerialized($this->filesStorage, $model->playerSRC ?: '');
        $isVideo = $filesystemItems->first()?->isVideo() ?? false;

        if (!$sourceFiles = $this->getSourceFiles($filesystemItems)) {
            return new Response();
        }

        // Compile data
        $figureData = $isVideo
            ? $this->buildVideoFigureData($model, $sourceFiles)
            : $this->buildAudioFigureData($model, $sourceFiles);

        $template->set('figure', (object) $figureData);
        $template->set('source_files', $sourceFiles);

        return $template->getResponse();
    }

    /**
     * @param list<FilesystemItem> $sourceFiles
     *
     * @return array<string, array<string, string|HtmlAttributes|list<HtmlAttributes>>|string>
     *
     * @phpstan-return FigureData
     */
    private function buildVideoFigureData(ContentModel $model, array $sourceFiles): array
    {
        $poster = null;

        if ($uuid = $model->posterSRC) {
            $filesModel = $this->getContaoAdapter(FilesModel::class);
            $poster = $filesModel->findByUuid($uuid);
        }

        $size = StringUtil::deserialize($model->playerSize, true);

        $attributes = $this->parsePlayerOptions($model)
            ->setIfExists('poster', $poster?->path)
            ->setIfExists('width', $size[0] ?? null)
            ->setIfExists('height', $size[1] ?? null)
            ->setIfExists('preload', $model->playerPreload)
        ;

        $range = $model->playerStart || $model->playerStop
            ? sprintf('#t=%s', implode(',', [$model->playerStart ?: '', $model->playerStop ?: '']))
            : '';

        $captions = [$model->playerCaption];

        $sources = array_map(
            function (FilesystemItem $item) use (&$captions, $range): HtmlAttributes {
                $captions[] = ($item->getExtraMetadata()['metadata'] ?? null)?->getDefault()?->getCaption();

                return (new HtmlAttributes())
                    ->setIfExists('type', $item->getMimeType(''))
                    ->set('src', $this->publicUriByStoragePath[$item->getPath()].$range)
                ;
            },
            $sourceFiles,
        );

        return [
            'media' => [
                'type' => 'video',
                'attributes' => $attributes,
                'sources' => $sources,
            ],
            'metadata' => new Metadata([
                Metadata::VALUE_CAPTION => array_filter($captions)[0] ?? '',
            ]),
        ];
    }

    /**
     * @param list<FilesystemItem> $sourceFiles
     *
     * @return array<string, array<string, string|HtmlAttributes|list<HtmlAttributes>>|string>
     *
     * @phpstan-return FigureData
     */
    private function buildAudioFigureData(ContentModel $model, array $sourceFiles): array
    {
        $attributes = $this
            ->parsePlayerOptions($model)
            ->setIfExists('preload', $model->playerPreload)
        ;

        $captions = [$model->playerCaption];

        $sources = array_map(
            function (FilesystemItem $item) use (&$captions): HtmlAttributes {
                $captions[] = ($item->getExtraMetadata()['metadata'] ?? null)?->getDefault()?->getCaption();

                return (new HtmlAttributes())
                    ->setIfExists('type', $item->getMimeType(''))
                    ->set('src', (string) $this->publicUriByStoragePath[$item->getPath()])
                ;
            },
            $sourceFiles,
        );

        return [
            'media' => [
                'type' => 'audio',
                'attributes' => $attributes,
                'sources' => $sources,
            ],
            'metadata' => new Metadata([
                Metadata::VALUE_CAPTION => array_filter($captions)[0] ?? '',
            ]),
        ];
    }

    private function parsePlayerOptions(ContentModel $model): HtmlAttributes
    {
        $attributes = new HtmlAttributes(['controls' => true]);

        foreach (StringUtil::deserialize($model->playerOptions, true) as $option) {
            if ('player_nocontrols' === $option) {
                $attributes->unset('controls');
                continue;
            }

            $attributes->set(substr($option, 7));
        }

        return $attributes;
    }

    /**
     * @return list<FilesystemItem>
     */
    private function getSourceFiles(FilesystemItemIterator $filesystemItems): array
    {
        $filesystemItems = $filesystemItems->sort(SortMode::mediaTypePriority);
        $items = [];

        foreach ($filesystemItems as $item) {
            if (!$publicUri = $this->filesStorage->generatePublicUri($item->getPath())) {
                continue;
            }

            $items[] = $item;
            $this->publicUriByStoragePath[$item->getPath()] = $publicUri;
        }

        return $items;
    }
}
