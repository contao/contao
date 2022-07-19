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

use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\Filesystem\FileDownloadHelper;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\FilesystemUtil;
use Contao\CoreBundle\Filesystem\SortMode;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Image\Preview\MissingPreviewProviderException;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Image\Preview\UnableToGeneratePreviewException;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\LayoutModel;
use Contao\StringUtil;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Security;

/**
 * @ContentElement("download", category="files")
 * @ContentElement("downloads", category="files")
 */
class DownloadsController extends AbstractContentElementController
{
    public function __construct(
        private readonly Security $security,
        private readonly VirtualFilesystem $filesStorage,
        private readonly FileDownloadHelper $fileDownloadHelper,
        private readonly PreviewFactory $previewFactory,
        private readonly Studio $studio,
        private readonly string $projectDir,
        private readonly array|null $allowedFileExtensions = null,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        // Todo: Remove method and move logic into its own action, once we have
        //       a strategy how to handle permissions for downloads via a real
        //       route. See #4862 for more details.
        $this->handleDownload($request);

        $filesystemItems = $this->getFilesystemItems($model);

        // Sort elements; relay to client-side logic if list should be randomized
        if (null !== ($sortMode = SortMode::tryFrom($sortBy = $model->sortBy))) {
            $filesystemItems = $filesystemItems->sort($sortMode);
        }

        $template->set('sort_mode', $sortMode);
        $template->set('randomize_order', $randomize = ('random' === $sortBy));

        // Limit elements; use client-side logic for only displaying the first
        // $limit elements in case we are dealing with a random order
        if (($limit = $model->numberOfItems) > 0 && !$randomize) {
            $filesystemItems = $filesystemItems->limit($limit);
        }

        $template->set('limit', $limit > 0 && $randomize ? $limit : null);

        // Compile list of downloads
        $showPreviews = $model->showPreview;

        $downloads = array_map(
            fn (FilesystemItem $filesystemItem): array => [
                'href' => $this->generateDownloadUrl($filesystemItem, $model, $request),
                'file' => $filesystemItem,
                'show_file_previews' => $showPreviews,
                'file_previews' => $this->getPreviews($filesystemItem, $model),
            ],
            iterator_to_array($filesystemItems)
        );

        // Explicitly define title/text metadata for a single file
        if ('download' === $model->type && $model->overwriteLink && $downloads) {
            $downloads[0]['title'] = $model->linkTitle;
            $downloads[0]['text'] = $model->titleText;
        }

        $template->set('downloads', $downloads);

        return $template->getResponse();
    }

    /**
     * Retrieve selected filesystem items but filter out those, that do not
     * match the current DCA and configuration constraints.
     */
    private function getFilesystemItems(ContentModel $model): FilesystemItemIterator
    {
        $sources = match (true) {
            'download' === $model->type => [$model->singleSRC],
            $model->useHomeDir && ($user = $this->security->getUser()) instanceof FrontendUser && $user->assignDir && ($homeDir = $user->homeDir) => $homeDir,
            default => $model->multiSRC,
        };

        // Find filesystem items
        $filesystemItems = FilesystemUtil::listContentsFromSerialized($this->filesStorage, $sources);

        // Optionally filter out files without metadata
        if ('downloads' === $model->type && $model->metaIgnore) {
            $filesystemItems = $filesystemItems->filter(
                static fn (FilesystemItem $item): bool =>
                    /** @var MetadataBag|null $metadata */
                    null !== ($metadata = $item->getExtraMetadata()['metadata'] ?? null) &&
                    null !== $metadata->getDefault()
            );
        }

        // Only allow certain file extensions
        $getAllowedFileExtensions = function (): array {
            if (null !== $this->allowedFileExtensions) {
                return $this->allowedFileExtensions;
            }

            $this->initializeContaoFramework();

            return StringUtil::trimsplit(',', $this->getContaoAdapter(Config::class)->get('allowedDownload'));
        };

        return $filesystemItems->filter(
            static fn (FilesystemItem $item): bool => \in_array(
                Path::getExtension($item->getPath(), true),
                array_map('strtolower', $getAllowedFileExtensions()),
                true
            )
        );
    }

    /**
     * If the content should be displayed inline or if the resource does not
     * have a public URI, a URL pointing to this controller's download action
     * will be generated, otherwise the direct download URL will be returned.
     */
    private function generateDownloadUrl(FilesystemItem $filesystemItem, ContentModel $model, Request $request): string
    {
        $path = $filesystemItem->getPath();
        $inline = $model->inline;

        if (!$inline && null !== ($publicUri = $this->filesStorage->generatePublicUri($path))) {
            return (string) $publicUri;
        }

        // Todo: Use an exclusive route once we have a strategy how to handle
        //       permissions for it. Right now we use the current route and
        //       then throw a ResponseException to initiate the download.
        $currentRoute = $request->attributes->get('_route');
        $context = ['id' => $model->id];

        return $inline
            ? $this->fileDownloadHelper->generateInlineUrl($currentRoute, $path, $context)
            : $this->fileDownloadHelper->generateDownloadUrl($currentRoute, $path, $filesystemItem->getName(), $context);
    }

    /**
     * Generate file preview images on the fly.
     *
     * @return \Generator<Figure>
     */
    private function getPreviews(FilesystemItem $filesystemItem, ContentModel $model): \Generator
    {
        $path = $filesystemItem->getPath();

        $figureBuilder = $this->studio
            ->createFigureBuilder()
            ->setSize($size = $model->size)
            ->enableLightbox($fullsize = $model->fullsize)
            ->disableMetadata()
            ->setLightboxGroupIdentifier(sprintf('dl_%s_%s', $model->id, md5($path)))
        ;

        $getLightboxSize = function (): string|null {
            $this->initializeContaoFramework();

            if (null === ($page = $this->getPageModel()) || null === $page->layout) {
                return null;
            }

            $layoutModel = $this->getContaoAdapter(LayoutModel::class)->findByPk($page->layout);

            return $layoutModel?->lightboxSize ?: null;
        };

        try {
            $previewSize = $this->previewFactory->getPreviewSizeFromImageSize(
                $fullsize ? $getLightboxSize() ?? $size : $size
            );

            $previews = $this->previewFactory->createPreviews(
                // TODO: As soon as our image libraries support this case, read from the public path instead.
                Path::join($this->projectDir, $this->filesStorage->getPrefix(), $path),
                $previewSize,
                $model->numberOfItems ?: PHP_INT_MAX
            );

            foreach ($previews as $image) {
                yield $figureBuilder->fromImage($image)->build();
            }
        } catch (UnableToGeneratePreviewException|MissingPreviewProviderException) {
            // ignore
        }
    }

    private function handleDownload(Request $request): void
    {
        $response = $this->fileDownloadHelper->handle(
            $request,
            $this->filesStorage,
            function (FilesystemItem $item, array $context): Response|null {
                if (
                    null === ($model = $this->getContaoAdapter(ContentModel::class)->findById($context['id'] ?? null)) ||
                    !$this->getFilesystemItems($model)->any(static fn (FilesystemItem $listItem) => $listItem->getPath() === $item->getPath())
                ) {
                    return new Response('The resource can not be accessed.', Response::HTTP_FORBIDDEN);
                }

                return null;
            }
        );

        if ($response instanceof StreamedResponse) {
            throw new ResponseException($response);
        }
    }
}
