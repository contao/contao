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
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
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
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractDownloadContentElementController extends AbstractContentElementController
{
    public function __invoke(Request $request, ContentModel $model, string $section, array|null $classes = null): Response
    {
        // TODO: Remove method and move logic into its own action, once we have
        // a strategy how to handle permissions for downloads via a real route.
        // See #4862 for more details.
        $this->handleDownload($request, $model);

        return parent::__invoke($request, $model, $section, $classes);
    }

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

    protected function compileDownloadsList(FilesystemItemIterator $filesystemItems, ContentModel $model, Request $request): array
    {
        return array_map(
            fn (FilesystemItem $filesystemItem): array => [
                'href' => $this->generateDownloadUrl($filesystemItem, $model, $request),
                'file' => $filesystemItem,
                'show_file_previews' => $model->showPreview,
                'file_previews' => $this->getPreviewsForContentModel($filesystemItem, $model),
            ],
            iterator_to_array($filesystemItems),
        );
    }

    protected function handleDownload(Request $request, ContentModel $model): void
    {
        $response = $this->container->get('contao.filesystem.file_download_helper')->handle(
            $request,
            $this->getVirtualFilesystem(),
            function (FilesystemItem $item, array $context) use ($request, $model): Response|null {
                // Do not handle downloads from other DownloadController
                // elements on the same page (see #5568)
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

    protected function applyDownloadableFileExtensionsFilter(FilesystemItemIterator $filesystemItemIterator): FilesystemItemIterator
    {
        // Only allow certain file extensions
        $getAllowedFileExtensions = function (): array {
            if (null !== $this->getParameter('contao.downloadable_files')) {
                return $this->getParameter('contao.downloadable_files');
            }

            $this->initializeContaoFramework();

            return StringUtil::trimsplit(',', $this->getContaoAdapter(Config::class)->get('allowedDownload'));
        };

        return $filesystemItemIterator->filter(
            static fn (FilesystemItem $item): bool => \in_array(
                Path::getExtension($item->getPath(), true),
                array_map('strtolower', $getAllowedFileExtensions()),
                true,
            ),
        );
    }

    /**
     * If the content should be displayed inline or if the resource does not
     * have a public URI, a URL pointing to this controller's download action
     * will be generated, otherwise the direct download URL will be returned.
     */
    protected function generateDownloadUrl(FilesystemItem $filesystemItem, ContentModel $model, Request $request): string
    {
        $path = $filesystemItem->getPath();
        $inline = $model->inline;

        if ($publicUri = $this->getVirtualFilesystem()->generatePublicUri($path, new ContentDispositionOption($inline))) {
            return (string) $publicUri;
        }

        // TODO: Use an exclusive route once we have a strategy how to handle
        // permissions for it. Right now we use the current route and then
        // throw a ResponseException to initiate the download.
        $currentUrl = $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo();
        $context = ['id' => $model->id];

        return $inline
            ? $this->container->get('contao.filesystem.file_download_helper')->generateInlineUrl($currentUrl, $path, $context)
            : $this->container->get('contao.filesystem.file_download_helper')->generateDownloadUrl($currentUrl, $path, $filesystemItem->getName(), $context);
    }

    /**
     * Generate file preview images on the fly for a content model (default Contao controller behaviour).
     *
     * @return \Generator<Figure>
     */
    protected function getPreviewsForContentModel(FilesystemItem $filesystemItem, ContentModel $model): \Generator
    {
        $figureBuilder = $this->container->get('contao.image.studio')
            ->createFigureBuilder()
            ->setSize($size = $model->size)
            ->enableLightbox($fullsize = (bool) $model->fullsize)
            ->disableMetadata()
            ->setLightboxGroupIdentifier(sprintf('dl_%s_%s', $model->id, md5($filesystemItem->getPath())))
        ;

        $getLightboxSize = function (): string|null {
            $this->initializeContaoFramework();

            if ((!$page = $this->getPageModel()) || null === $page->layout) {
                return null;
            }

            $layoutModel = $this->getContaoAdapter(LayoutModel::class)->findByPk($page->layout);

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

        // TODO: As soon as our image libraries support this case, read from the public path instead and drop this check
        if (!method_exists($vfs, 'getPrefix')) {
            throw new \LogicException('Your virtual file system has to implement the getPrefix() method for now!');
        }

        try {
            $previewSize = $this->container->get('contao.image.preview_factory')->getPreviewSizeFromImageSize($size);

            $previews = $this->container->get('contao.image.preview_factory')->createPreviews(
                // TODO: As soon as our image libraries support this case, read from the public path instead.
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
