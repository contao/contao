<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\Filesystem\FileDownloadHelper;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\PublicUri\ContentDispositionOption;
use Contao\CoreBundle\Filesystem\PublicUri\Options;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Image\Preview\MissingPreviewProviderException;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Image\Preview\UnableToGeneratePreviewException;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\Image\PictureConfiguration;
use Contao\Image\ResizeConfiguration;
use Contao\LayoutModel;
use Contao\StringUtil;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractDownloadContentElementController extends AbstractContentElementController
{
    /**
     * @return array<string>
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.filesystem.file_download_helper'] = FileDownloadHelper::class;
        $services['contao.image.preview_factory'] = PreviewFactory::class;
        $services['contao.image.studio'] = Studio::class;

        return $services;
    }

    abstract protected function getVirtualFilesystem(): VirtualFilesystemInterface;

    abstract protected function getFilesystemItems(Request $request, ContentModel $model): FilesystemItemIterator;

    protected function compileDownloadsList(FilesystemItemIterator $filesystemItems, ContentModel $model): array
    {
        $items = array_map(
            fn (FilesystemItem $filesystemItem): array => [
                'href' => $this->generateDownloadUrl($filesystemItem, $model),
                'file' => $filesystemItem,
                'show_file_previews' => $model->showPreview,
                'file_previews' => $this->getPreviewsForContentModel($filesystemItem, $model),
            ],
            iterator_to_array($filesystemItems),
        );

        return array_values(array_filter(
            $items,
            static fn (array $item) => null !== $item['href'],
        ));
    }

    protected function applyDownloadableFileExtensionsFilter(FilesystemItemIterator $filesystemItemIterator): FilesystemItemIterator
    {
        $this->initializeContaoFramework();

        $allowedDownload = StringUtil::trimsplit(',', $this->getContaoAdapter(Config::class)->get('allowedDownload'));

        return $filesystemItemIterator->filter(
            static fn (FilesystemItem $item): bool => \in_array(
                Path::getExtension($item->getPath(), true),
                array_map(strtolower(...), $allowedDownload),
                true,
            ),
        );
    }

    /**
     * If the content should be displayed inline or if the resource does not have a
     * public URI, a URL pointing to this controller's download action will be
     * generated, otherwise the direct download URL will be returned.
     *
     * Note: You have to check permissions yourself **before** calling this
     */
    protected function generateDownloadUrl(FilesystemItem $filesystemItem, ContentModel $model): string|null
    {
        $options = Options::create()
            ->withSetting(Options::OPTION_CONTENT_DISPOSITION_TYPE, new ContentDispositionOption($model->inline))
        ;

        // If there is a page model, take the ttl from the shared max age. Otherwise
        // (backend preview), just set it to 0.
        $pageModel = $this->getPageModel();
        $ttl = $pageModel ? $this->getSharedMaxAge($pageModel) : 0;

        $uri = $this->generatePublicUriWithTemporaryAccess(
            $this->getVirtualFilesystem(),
            $filesystemItem,
            $ttl,
            ['id' => $model->id, 'tstamp' => $model->tstamp],
            $options,
        );

        return $uri instanceof UriInterface ? (string) $uri : null;
    }

    /**
     * Generate file preview images on the fly for a content model (default Contao
     * controller behavior).
     *
     * @return \Generator<Figure>
     */
    protected function getPreviewsForContentModel(FilesystemItem $filesystemItem, ContentModel $model): \Generator
    {
        $figureBuilder = $this->container->get('contao.image.studio')
            ->createFigureBuilder()
            ->setSize($size = $model->size)
            ->enableLightbox($fullsize = $model->fullsize)
            ->disableMetadata()
            ->setLightboxGroupIdentifier(\sprintf('dl_%s_%s', $model->id, md5($filesystemItem->getPath())))
        ;

        $getLightboxSize = function (): string|null {
            $this->initializeContaoFramework();

            if ((!$page = $this->getPageModel()) || null === $page->layout) {
                return null;
            }

            $layoutModel = $this->getContaoAdapter(LayoutModel::class)->findById($page->layout);

            return $layoutModel?->lightboxSize ?: null;
        };

        return $this->getPreviews($filesystemItem, $figureBuilder, $fullsize ? $getLightboxSize() ?? $size : $size, $model->numberOfItems ?: PHP_INT_MAX);
    }

    /**
     * Generate file preview images on the fly for a custom configuration.
     *
     * @return \Generator<Figure>
     */
    protected function getPreviews(FilesystemItem $filesystemItem, FigureBuilder $figureBuilder, PictureConfiguration|ResizeConfiguration|array|int|string|null $size, int $numberOfItems = PHP_INT_MAX): \Generator
    {
        $path = $filesystemItem->getPath();
        $vfs = $this->getVirtualFilesystem();

        // TODO: As soon as our image libraries support this case, read from the public
        // path instead and drop this check
        if (!method_exists($vfs, 'getPrefix')) {
            throw new \LogicException('Your virtual file system has to implement the getPrefix() method for now!');
        }

        try {
            $previewSize = $this->container->get('contao.image.preview_factory')->getPreviewSizeFromImageSize($size);

            $previews = $this->container->get('contao.image.preview_factory')->createPreviews(
                // TODO: As soon as our image libraries support this case, read from the public
                // path instead.
                Path::join($this->getParameter('kernel.project_dir'), $vfs->getPrefix(), $path),
                $previewSize,
                $numberOfItems,
            );

            foreach ($previews as $image) {
                yield $figureBuilder->fromImage($image)->build();
            }
        } catch (UnableToGeneratePreviewException|MissingPreviewProviderException) {
            // ignore
        }
    }
}
