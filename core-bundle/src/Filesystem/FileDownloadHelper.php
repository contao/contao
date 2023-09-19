<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

use Contao\StringUtil;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\UriSigner;

/**
 * This helper class makes it easier to generate and handle streamed file
 * downloads. In order to use it there are two steps.
 *
 *  1) Generate a signed URL for a file by calling generateInlineUrl() or
 *     generateDownloadUrl() with the controller that should handle the
 *     download.
 *
 *  2) In the controller's action, call handle(), which will generate a
 *     StreamedResponse of the requested file's content. The URL signature as
 *     well as the file's existence in the storage are already verified for
 *     you - if you want to add additional checks, there is a closure you can
 *     hook into.
 *
 * @internal
 */
class FileDownloadHelper
{
    private const PARAM_PATH = 'p';

    private const PARAM_CONTEXT = 'ctx';

    private const PARAM_DISPOSITION = 'd';

    private const PARAM_FILE_NAME = 'f';

    public function __construct(private readonly UriSigner $signer)
    {
    }

    /**
     * Generate a signed file URL that a browser will display inline.
     *
     * You can optionally provide an array of $context, that will also be
     * incorporated into the URL.
     */
    public function generateInlineUrl(string $url, string $path, array|null $context = null): string
    {
        return $this->generate($url, [
            self::PARAM_PATH => $path,
            self::PARAM_CONTEXT => null !== $context ? serialize($context) : null,
        ]);
    }

    /**
     * Generate a signed file URL that a browser will download.
     *
     * You can optionally provide an array of $context, that will also be
     * incorporated into the URL.
     */
    public function generateDownloadUrl(string $url, string $path, string|null $fileName = null, array|null $context = null): string
    {
        if (null !== $fileName) {
            // Call makeDisposition() here to check if the file name is valid
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName, 'f');
        }

        return $this->generate($url, [
            self::PARAM_PATH => $path,
            self::PARAM_DISPOSITION => HeaderUtils::DISPOSITION_ATTACHMENT,
            self::PARAM_FILE_NAME => $fileName,
            self::PARAM_CONTEXT => null !== $context ? serialize($context) : null,
        ]);
    }

    /**
     * Handle download request and stream file contents.
     *
     * If you need to add custom logic, you can implement the $onProcess
     * closure that gets called with a FilesystemItem object and the context
     * defined when generating the URL. You can shortcut operation by returning
     * your own response there, otherwise return null.
     *
     * @param (\Closure(FilesystemItem, array):Response|null)|null $onProcess
     */
    public function handle(Request $request, VirtualFilesystemInterface $storage, \Closure|null $onProcess = null): Response
    {
        if (!$this->signer->checkRequest($request)) {
            return new Response('The provided file URL is not valid.', Response::HTTP_FORBIDDEN);
        }

        if (!$file = $this->getFile($request, $storage)) {
            return new Response('The requested resource does not exist.', Response::HTTP_NOT_FOUND);
        }

        if (null !== $onProcess) {
            $context = StringUtil::deserialize($request->query->get(self::PARAM_CONTEXT, ''), true);
            $response = $onProcess($file, $context);

            if ($response instanceof Response) {
                return $response;
            }
        }

        $stream = $storage->readStream($file->getPath());
        $metadata = stream_get_meta_data($stream);

        // Prefer sendfile for local resources
        if ('STDIO' === $metadata['stream_type'] && 'plainfile' === $metadata['wrapper_type'] && Path::isAbsolute($localPath = $metadata['uri'])) {
            $response = new BinaryFileResponse($localPath);
        } else {
            $response = new StreamedResponse(
                static function () use ($stream): void {
                    stream_copy_to_stream($stream, fopen('php://output', 'w'));
                },
            );
        }

        $this->addContentTypeHeader($response, $file);
        $this->addContentDispositionHeader($response, $request, $file);

        return $response;
    }

    private function generate(string $url, array $params): string
    {
        return $this->signer->sign($url.'?'.http_build_query(array_filter($params)));
    }

    private function getFile(Request $request, VirtualFilesystemInterface $storage): FilesystemItem|null
    {
        $path = $request->query->get(self::PARAM_PATH, '');

        try {
            $file = $storage->get($path, VirtualFilesystemInterface::BYPASS_DBAFS);
        } catch (VirtualFilesystemException) {
            return null;
        }

        return $file;
    }

    private function addContentTypeHeader(Response $response, FilesystemItem $file): void
    {
        $response->headers->set('Content-Type', $file->getMimeType());
    }

    private function addContentDispositionHeader(Response $response, Request $request, FilesystemItem $file): void
    {
        if (null === ($dispositionType = $request->query->get(self::PARAM_DISPOSITION))) {
            return;
        }

        $fileName = $request->query->get(self::PARAM_FILE_NAME, basename($file->getPath()));
        $fileNameFallback = mb_convert_encoding($fileName, 'UTF-8', 'ASCII');
        $disposition = HeaderUtils::makeDisposition($dispositionType, $fileName, $fileNameFallback);

        $response->headers->set('Content-Disposition', $disposition);
    }
}
