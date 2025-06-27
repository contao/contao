<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem\FileManager\Operation;

use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForFileManagerElements;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
#[AsOperationForFileManagerElements]
class ElementsMoveOperation extends AbstractElementsOperation
{
    public function canExecute(ElementsOperationContext $context): bool
    {
        return true;
    }

    public function execute(Request $request, ElementsOperationContext $context): Response|null
    {
        $count = $context->getFilesystemItems()->count();

        // Show a dialog to select a target
        if (!$request->request->has('target')) {
            return $this->render('@Contao/backend/file_manager/operation/elements/move.stream.html.twig', [
                'num_elements' => $count,
            ]);
        }

        /*
        $numError = 0;
        $numSuccess = 0;

        for($i = 0; $i < $count; $i++) {
            try {
                //$context->getStorage()->move(…, $target);

                $numSuccess++;
            } catch (VirtualFilesystemException $e) {
                $numError++;
            }
        }

        $response = $this->render('@Contao/backend/file_manager/operation/elements/rename_result.stream.html.twig', [
            'path' => $context->getViewPath(),
            'num_elements' => $numSuccess,
        ]);

        if (!$numError) {
            return $response;
        }

        return new Response(
            $this->error($context, affectedElements: $numError)->getContent().$response->getContent(),
        );
        */

        return $this->success($context);
    }
}
