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
class ElementsRenameOperation extends AbstractElementsOperation
{
    public function canExecute(ElementsOperationContext $context): bool
    {
        return !$context->hasMixedTypes();
    }

    public function execute(Request $request, ElementsOperationContext $context): Response|null
    {
        /** @var FilesystemItem $prototypeElement */
        $prototypeElement = $context->getFilesystemItems()->first();
        $name = $prototypeElement->getName(true);

        $count = $context->getFilesystemItems()->count();
        $single = $count === 1;

        // Show a confirmation dialog
        if (!$newName = $request->request->getString('name')) {
            $getExtensions = static function() use ($context, $prototypeElement) {
                if(!$prototypeElement->isFile()) {
                    return null;
                }

                $extensions = array_unique(
                    array_map(
                        static fn(FilesystemItem $item):string => $item->getExtension(true),
                        $context->getFilesystemItems()->toArray()
                    )
                );

                sort($extensions);

                return $extensions;
            };

            return $this->render('@Contao/backend/file_manager/operation/elements/rename.stream.html.twig', [
                'allowed_name_pattern' => $single ? $this->buildAllowedNamePattern($context) : '',
                'suggested_name' => $single ? $name : "$name #",
                'extensions' => $getExtensions(),
                'num_elements' => $count,
                'multiple' => !$single,
            ]);
        }

        $startWith = $request->request->getInt('start_with', 1);

        $storage = $context->getStorage();
        $items = $context->getFilesystemItems()->toArray();

        $numError = 0;
        $numSuccess = 0;

        for($i = 0; $i < $count; $i++) {
            $current = $items[$i];

            // Build and find the first non-conflicting path
            for($try = 0; true; $try++) {
                $constructedName = sprintf(
                    '%s%s',
                    $single ? $newName : str_replace('#', (string)($startWith + $i), $newName),
                    $try > 0 ? " ($try)" : ''
                );

                $newPath = Path::join(
                    Path::getDirectory($current->getPath()),
                    $constructedName . ($current->isFile() ? ".{$current->getExtension()}" : '')
                );

                if(!$storage->has($newPath)) {
                    break;
                }
            }

            try {
                $context->getStorage()->move($current->getPath(), $newPath);

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
    }

    private function buildAllowedNamePattern(ElementsOperationContext $context): string
    {
        $existingNames = array_map(
            static fn(FilesystemItem $item): string => $item->getName(true),
            $context->getStorage()->listContents($context->getViewPath())->toArray()
        );

        // Disallow selecting a name of an existing item
        return \sprintf(
            '^(?!(%s)$).*',
            implode('|', array_map(preg_quote(...), $existingNames)),
        );
    }
}
