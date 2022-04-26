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
use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\File;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PlayerController extends AbstractContentElementController
{
    private const VIDEO_TYPES = ['mp4', 'm4v', 'mov', 'wmv', 'webm', 'ogv'];
    private const AUDIO_TYPES = ['m4a', 'mp3', 'wma', 'mpeg', 'wav', 'ogg'];

    private string $currentLocale = '';
    private string $currentCaption = '';

    public function __construct(private ContaoContext $assetsContext)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $this->initializeContaoFramework();

        // Get files
        if (empty($files = $this->getFiles($model))) {
            return new Response();
        }

        // Order them by their file extension
        $isVideo = \in_array(array_key_first($files), self::VIDEO_TYPES, true);
        $orderRelation = array_flip($isVideo ? self::VIDEO_TYPES : self::AUDIO_TYPES);

        uksort($files, static fn (string $a, string $b): int => ($orderRelation[$a] ?? null) <=> ($orderRelation[$b] ?? null));

        // Compile data
        $this->currentCaption = $model->caption;
        $this->currentLocale = '';

        $playerOptions = $this->parsePlayerOptions($model);

        $figure = [
            'media' => $isVideo ?
                $this->buildVideoAttributes($model, $playerOptions, $files) :
                $this->buildAudioAttributes($model, $playerOptions, $files),
            'caption' => $this->currentCaption,
        ];

        $template->set('figure', $figure);

        // todo: should we pass FilesystemItems here?
        $template->set('files_list', $this->getFilesList($files));

        return $template->getResponse();
    }

    /**
     * @return array<string, FilesModel>
     */
    private function getFiles(ContentModel $model): array
    {
        $filesModel = $this->getContaoAdapter(FilesModel::class);

        $models = $filesModel->findMultipleByUuidsAndExtensions(
            StringUtil::deserialize($model->playerSRC, true),
            [...self::VIDEO_TYPES, ...self::AUDIO_TYPES]
        );

        $files = [];

        foreach ($models as $file) {
            $files[$file->extension] = $file;
        }

        return $files;
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
     * @param array<string, FilesModel> $files
     *
     * @return array{type: string, attributes: HtmlAttributes, sources: array<HtmlAttributes>}
     */
    private function buildVideoAttributes(ContentModel $model, HtmlAttributes $playerAttributes, array $files): array
    {
        $poster = null;

        if ($uuid = $model->posterSRC) {
            $filesModel = $this->getContaoAdapter(FilesModel::class);
            $poster = $filesModel->findByUuid($uuid);
        }

        $size = StringUtil::deserialize($model->playerSize, true);

        $attributes = $playerAttributes
            ->setIfExists('poster', $poster?->path)
            ->setIfExists('width', $size[0] ?? null)
            ->setIfExists('height', $size[1] ?? null)
            ->setIfExists('preload', $model->playerPreload)
        ;

        $range = $model->playerStart || $model->playerStop ? sprintf(
            't=%s',
            implode(',', [$model->playerStart ?: '', $model->playerStop ?: ''])
        ) : '';

        $sources = array_map(
            function (FilesModel $file) use ($range): HtmlAttributes {
                $this->updateCaption($file);

                return (new HtmlAttributes())
                    ->setIfExists('type', $GLOBALS['TL_MIME'][$file->extension][0] ?? '')
                    ->set('src', $file->path.$range)
                ;
            },
            $files
        );

        return [
            'type' => 'video',
            'attributes' => $attributes,
            'sources' => $sources,
        ];
    }

    /**
     * @param array<string, FilesModel> $files
     *
     * @return array{type: string, attributes: HtmlAttributes, sources: array<HtmlAttributes>}
     */
    private function buildAudioAttributes(ContentModel $model, HtmlAttributes $playerAttributes, array $files): array
    {
        $attributes = $playerAttributes
            ->setIfExists('preload', $model->playerPreload)
        ;

        $sources = array_map(
            function (FilesModel $file): HtmlAttributes {
                $this->updateCaption($file);

                return (new HtmlAttributes())
                    ->set('type', (new File($file->path))->mime)
                    ->set('src', $file->path)
                ;
            },
            $files
        );

        return [
            'type' => 'audio',
            'attributes' => $attributes,
            'sources' => $sources,
        ];
    }

    private function updateCaption(FilesModel $file): void
    {
        if ($caption = $file->getMetadata($this->currentLocale)?->getCaption()) {
            $this->currentCaption = $caption;
        }
    }

    /**
     * @param array<FilesModel> $files
     *
     * @return array<array{icon: string, name: string}}>
     */
    private function getFilesList(array $files): array
    {
        $basePath = Path::join($this->assetsContext->getStaticUrl(), 'assets/contao/images');

        return array_map(
            static fn (FilesModel $file) => [
                'icon' => Path::join($basePath, $GLOBALS['TL_MIME'][$file->extension][1] ?? ''),
                'extension' => $file->extension,
                'fileName' => $file->name,
                'fileSize' => System::getReadableSize((new File($file->path))->size),
            ],
            $files
        );
    }
}
