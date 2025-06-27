<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem\FileManager\Operation;

use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForFileManagerElements;
use Contao\CoreBundle\Filesystem\FileDownloadHelper;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @experimental
 */
#[AsOperationForFileManagerElements]
class ElementsDownloadOperation extends AbstractElementsOperation
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FileDownloadHelper $downloadHelper
    )
    {
    }

    public function canExecute(ElementsOperationContext $context): bool
    {
        return $context->getFilesystemItems()->count() === 1 && $context->getFilesystemItems()->first()->isFile();
    }

    public function execute(Request $request, ElementsOperationContext $context): Response|null
    {
        /** @var FilesystemItem $item */
        $item = $context->getFilesystemItems()->first();

        $downloadUrl = $this->downloadHelper->generateDownloadUrl(
            $this->urlGenerator->generate(
                '_contao_file_manager_download',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $item->getPath()
        );

        return $this->render('@Contao/backend/file_manager/operation/elements/download.stream.html.twig', [
            'url' => $downloadUrl,
        ]);
    }
}
