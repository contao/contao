<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem\FileManager\Operation;

use Contao\CoreBundle\DataContainer\DcaRequestSwitcher;
use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForFileManagerElements;
use Contao\CoreBundle\Filesystem\Dbafs\RetrieveDbafsMetadataEvent;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\DC_Folder;
use Contao\DcaLoader;
use Contao\System;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
#[AsOperationForFileManagerElements]
class ElementsEditOperation extends AbstractElementsOperation
{
    public function __construct(
        private readonly DcaRequestSwitcher       $dcaRequestSwitcher,
        private readonly EventDispatcherInterface $eventDispatcher,
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

        // For now, we simply render the DC_Folder edit form - thus only files
        // in "<project-dir>/files" can be accessed. Later, we will use Symfony
        // forms and render a proper edit mask independent of DC_Folder.
        if(!$context->getStorage() instanceof VirtualFilesystem || $context->getStorage()->getPrefix() !== 'files') {
            return $this->error($context, 'message.elements_edit.not_available');
        }

        // Render edit form
        if (!$request->request->has('save')) {
            return $this->render('@Contao/backend/file_manager/operation/elements/edit_legacy_form.stream.html.twig', [
                'edit_form' => $this->formatLegacyHtml($this->generateLegacyEditForm($request, $item->getPath())),
                'item' => $item,
            ]);
        }

        // Compile extra metadata
        $rawData = [
            ...array_map(
                static fn($value) => \is_array($value) ? serialize($value) : $value,
                array_diff_key($request->request->all(), ['FORM_SUBMIT' => null, 'REQUEST_TOKEN' => null])
            ),
            'path' => $item->getPath(),
            'uuid' => $item->getUuid()?->toBinary(),
        ];

        $event = new RetrieveDbafsMetadataEvent('tl_files', $rawData);
        $this->eventDispatcher->dispatch($event);

        // Update element
        $context->getStorage()->setExtraMetadata(
            $item->getPath(),
            $item->getExtraMetadata()->with($event->getExtraMetadata())
        );

        return $this->success($context);
    }

    private function generateLegacyEditForm(Request $request, string $path): string {
        $subRequest = new Request([
            'do' => 'files',
            'act' => 'edit',
            'id' => Path::join('files', $path),
        ]);

        $subRequest->setSession($request->getSession());

        return $this->dcaRequestSwitcher->runWithRequest($subRequest, function() {
            (new DcaLoader('tl_files'))->load();
            System::loadLanguageFile('tl_files');

            return (new DC_Folder('tl_files'))->edit();
        });
    }

    private function formatLegacyHtml(string $html): string {
        $xml = new \DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->loadHTML('<?xml encoding="UTF-8">'.$html);

        // Remove title widget
        $xml->getElementById('ctrl_name')?->parentElement->remove();

        // Remove submit buttons
        foreach($xml->getElementsByTagName('div') as $element) {
            if($element->getAttribute('class') === 'tl_formbody_submit') {
                $element->remove();

                break;
            }
        }

        return $xml->saveHTML($xml->getElementById('tl_files')->firstElementChild);
    }
}
