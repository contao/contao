<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\Exception\ResponseException;
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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * @deprecated Deprecated since Contao 6.0, to be removed in Contao 7;
     *             leave handling the downloads to the new FileStreamController.
     */
    protected function handleDownload(Request $request, ContentModel $model): void
    {
        trigger_deprecation('contao/core-bundle', '6.0', 'Using "%s()" is deprecated and will no longer work in Contao 7. Leave this to the new FileStreamController.', __METHOD__);

        $response = $this->container->get('contao.filesystem.file_download_helper')->handle(
            $request,
            $this->getVirtualFilesystem(),
            function (FilesystemItem $item, array $context) use ($model, $request): Response|null {
                // Do not handle downloads from other DownloadController elements on the same
                // page (see #5568)
                if ($model->id !== ($context['id'] ?? null)) {
                    return new Response('', Response::HTTP_NO_CONTENT);
                }

                if (!$this->getFilesystemItems($request, $model)->any(static fn (FilesystemItem $listItem) => $listItem->getPath() === $item->getPath())) {
                    return new Response('The resource can not be accessed anymore.', Response::HTTP_GONE);
                }

                return null;
            },
        );

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            throw new ResponseException($response);
        }
    }

    /**
     * Backwards compatibility: keep the $request argument until Contao 7.
     *
     * @noinspection PhpUnusedParameterInspection
     */
    protected function compileDownloadsList(FilesystemItemIterator $filesystemItems, ContentModel $model, Request $request): array
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

        $uri = $this->generatePublicUriWithTemporaryAccess(
            $this->getVirtualFilesystem(),
            $filesystemItem,
            ['id' => $model->id, 'tstamp' => $model->tstamp],
            null,
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
