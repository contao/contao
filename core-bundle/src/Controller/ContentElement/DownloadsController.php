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
use Contao\CoreBundle\Filesystem\FileDownloadHelper;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\FilesystemUtil;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Image\Preview\MissingPreviewProviderException;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Image\Preview\UnableToGeneratePreviewException;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\LayoutModel;
use Contao\StringUtil;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class DownloadsController extends AbstractContentElementController
{
    private const DOWNLOAD_ROUTE_NAME = 'contao_download';

    public function __construct(private Security $security, private VirtualFilesystemInterface $filesStorage, private Studio $studio, private FileDownloadHelper $fileDownloadHelper, private PreviewFactory $previewFactory)
    {
    }

    #[Route('/_download', name: self::DOWNLOAD_ROUTE_NAME)]
    public function downloadAction(Request $request): Response
    {
        $this->initializeContaoFramework();

        return $this->fileDownloadHelper->handle(
            $request,
            $this->filesStorage,
            function (FilesystemItem $item, array $context): Response|null {
                if (
                    null === ($id = ($context['id'] ?? null)) ||
                    null === ($model = ContentModel::findById($id)) ||
                    !$this->getFilesystemItems($model)->any(static fn (FilesystemItem $listItem) => $listItem->getPath() === $item->getPath())
                ) {
                    return new Response('The resource can not be accessed.', Response::HTTP_FORBIDDEN);
                }

                return null;
            }
        );
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        // Find and sort filesystem items
        $filesystemItems = $this->getFilesystemItems($model);
        // todo: optionally skip if there is no metadata

        if (\in_array(($sortBy = $model->sortBy), FilesystemItemIterator::$supportedSortingModes, true)) {
            $filesystemItems = $filesystemItems->sort($sortBy);
        }

        // Compile download collection
        $downloads = array_map(
            fn (FilesystemItem $filesystemItem): array => [
                'file' => $filesystemItem,
                'name' => Path::getFilenameWithoutExtension($filesystemItem->getPath()),
                'extension' => Path::getExtension($filesystemItem->getPath()),
                'previews' => $this->getPreviews($filesystemItem, $model),
                'href' => $this->generateDownloadUrl($filesystemItem, $model),
            ],
            iterator_to_array($filesystemItems)
        );

        $template->set('downloads', $downloads);
        $template->set('sorting', $sortBy);
        $template->set('show_previews', (bool) $model->showPreview);

        return $template->getResponse();
    }

    private function getFilesystemItems(ContentModel $model): FilesystemItemIterator
    {
        $sources = match (true) {
            'download' === $model->type => [$model->singleSRC],
            $model->useHomeDir && ($user = $this->security->getUser()) instanceof FrontendUser && $user->assignDir && ($homeDir = $user->homeDir) => $homeDir,
            default => $model->multiSRC,
        };

        return FilesystemUtil::listContentsFromMultiSRC($this->filesStorage, $sources);
    }

    private function generateDownloadUrl(FilesystemItem $filesystemItem, ContentModel $model): string
    {
        $path = $filesystemItem->getPath();
        $context = ['id' => $model->id];

        return $model->inline ?
            $this->fileDownloadHelper->generateInlineUrl(self::DOWNLOAD_ROUTE_NAME, $path, $context) :
            $this->fileDownloadHelper->generateDownloadUrl(self::DOWNLOAD_ROUTE_NAME, $path, basename($path), $context);
    }

    /**
     * @return \Generator<Figure>
     */
    private function getPreviews(FilesystemItem $filesystemItem, ContentModel $model): \Generator
    {
        $path = $filesystemItem->getPath();

        // todo: refactor logic in preview factory, so that we do not need this here
        $getLightboxSize = function (): array|null {
            if (null === ($page = $this->getPageModel()) || null === $page->layout) {
                return null;
            }

            $this->initializeContaoFramework();
            $layoutModel = $this->getContaoAdapter(LayoutModel::class)->findByPk($page->layout);

            if (null === $layoutModel || empty($layoutModel->lightboxSize)) {
                return null;
            }

            return StringUtil::deserialize($layoutModel->lightboxSize, true);
        };

        $figureBuilder = $this->studio
            ->createFigureBuilder()
            ->setSize($size = $model->size)
            ->enableLightbox($fullsize = (bool) $model->fullsize)
            ->disableMetadata()
            ->setLightboxGroupIdentifier(sprintf('dl_%s_%s', $model->id, md5($path)))
        ;

        try {
            $previewSize = $this->previewFactory->getPreviewSizeFromImageSize(
                $fullsize ? $getLightboxSize() ?? $size : $size
            );

            $previews = $this->previewFactory->createPreviews(
                // todo: we should not need public paths as the source to create previews but stream from the VFS directly
                $filesystemItem->getPublicPath(),
                $previewSize,
                (int) $model->numberOfItems ?: PHP_INT_MAX
            );

            foreach ($previews as $image) {
                yield $figureBuilder->fromImage($image)->build();
            }
        } catch (UnableToGeneratePreviewException|MissingPreviewProviderException) {
            // ignore
        }
    }
}
