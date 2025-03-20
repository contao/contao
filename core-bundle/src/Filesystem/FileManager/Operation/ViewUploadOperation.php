<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem\FileManager\Operation;

use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForFileManagerView;
use Contao\CoreBundle\Filesystem\FileManager\Upload\UploadFileEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
#[AsOperationForFileManagerView]
class ViewUploadOperation extends AbstractViewOperation
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function canExecute(ViewOperationContext $context): bool
    {
        return true;
    }

    public function execute(Request $request, ViewOperationContext $context): Response|null
    {
        $targetDirectory = $context->getViewPath();

        /** @var UploadedFile|null $upload */
        $upload = $request->files->get('file');

        // If no file is present in the request, show a dialog to pick one
        if (null === $upload) {
            return $this->render('@Contao/backend/file_manager/operation/view/upload_select.stream.html.twig', [
                'target_directory' => $targetDirectory,
            ]);
        }

        if (!$upload->isValid()) {
            return $this->error($context);
        }

        // Validate and normalize
        $event = new UploadFileEvent($upload->getClientOriginalName());
        $this->eventDispatcher->dispatch($event);

        if($event->isDenied()) {
            return $this->render('@Contao/backend/file_manager/operation/view/upload_error.stream.html.twig', [
                'reasons' => $event->getDenyReasons(),
            ]);
        }

        // Stream temporary file into the storage
        $targetPath = Path::join($targetDirectory, $event->getFilename());

        $stream = fopen($upload->getRealPath(), 'rb+');
        $context->getStorage()->writeStream($targetPath, $stream);
        fclose($stream);

        return $this->render('@Contao/backend/file_manager/operation/view/upload_result.stream.html.twig', [
            'target_directory' => $targetDirectory,
        ]);
    }
}
