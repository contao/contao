<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem\FileManager\Operation;

use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForFileManagerElements;
use Contao\CoreBundle\Filesystem\VirtualFilesystemException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
#[AsOperationForFileManagerElements]
class ElementsDeleteOperation extends AbstractElementsOperation
{
    public function canExecute(ElementsOperationContext $context): bool
    {
        return true;
    }

    public function execute(Request $request, ElementsOperationContext $context): Response|null
    {
        // Show a confirmation dialog
        if (!$request->request->has('confirm_delete')) {
            return $this->render('@Contao/backend/file_manager/operation/elements/delete_confirm.stream.html.twig', [
                'items' => $context->getFilesystemItems(),
            ]);
        }

        $directoriesAffected = false;
        $currentViewAffected = false;
        $numSuccess = 0;
        $numError = 0;

        $viewPath = $context->getViewPath();

        foreach ($context->getFilesystemItems() as $filesystemItem) {
            try {
                $path = $filesystemItem->getPath();

                if ($filesystemItem->isFile()) {
                    $context->getStorage()->delete($path);
                } else {
                    $context->getStorage()->deleteDirectory($path);
                    $directoriesAffected = true;

                    if (Path::isBasePath($path, $viewPath)) {
                        $viewPath = Path::getDirectory($path);
                    }
                }

                if (Path::getDirectory($filesystemItem->getPath()) === $viewPath) {
                    $currentViewAffected = true;
                }

                ++$numSuccess;
            } catch (VirtualFilesystemException) {
                ++$numError;
            }
        }

        $response = $this->render('@Contao/backend/file_manager/operation/elements/delete_result.stream.html.twig', [
            'directories_affected' => $directoriesAffected,
            'current_view_affected' => $currentViewAffected,
            'path' => $viewPath,
            'num_elements' => $numSuccess,
        ]);

        if (!$numError) {
            return $response;
        }

        return new Response(
            $this->error($context, affectedElements: $numError)->getContent().$response->getContent(),
        );
    }
}
