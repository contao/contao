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

use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class FileDownloadHelper
{
    private const PARAM_PATH = 'p';
    private const PARAM_CONTEXT = 'cx';
    private const PARAM_DISPOSITION = 'd';
    private const PARAM_FILE_NAME = 'f';

    public function __construct(private RouterInterface $router, private UriSigner $signer)
    {
    }

    /**
     * Generate an URL that will be displayed in the browser.
     */
    public function generateInlineUrl(string $route, string $path, array|null $context = null): string
    {
        return $this->generate(
            $route,
            [
                self::PARAM_PATH => $path,
                self::PARAM_CONTEXT => $context,
            ]
        );
    }

    /**
     * Generate an URL that will start a download.
     */
    public function generateDownloadUrl(string $route, string $path, string|null $fileName = null, array|null $context = null): string
    {
        if (null !== $fileName) {
            // Make disposition to validate file name
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName, 'f');
        }

        return $this->generate(
            $route,
            [
                self::PARAM_PATH => $path,
                self::PARAM_DISPOSITION => HeaderUtils::DISPOSITION_ATTACHMENT,
                self::PARAM_FILE_NAME => $fileName,
                self::PARAM_CONTEXT => $context,
            ]
        );
    }

    /**
     * Handle download request and stream file contents.
     *
     * @param (\Closure(FilesystemItem,array):Response|null)|null $onProcess
     */
    public function handle(Request $request, VirtualFilesystemInterface $storage, \Closure|null $onProcess = null): Response
    {
        if (!$this->signer->checkRequest($request)) {
            return new Response('The file URL is not valid.', Response::HTTP_FORBIDDEN);
        }

        if (null === ($file = $this->getFile($request, $storage))) {
            return new Response('The resource does not exist (anymore).', Response::HTTP_NOT_FOUND);
        }

        if (null !== $onProcess) {
            $context = $request->query->get(self::PARAM_CONTEXT, []);

            if (null !== ($response = $onProcess($file, $context))) {
                return $response;
            }
        }

        $response = new StreamedResponse(
            static function () use ($storage, $file): void {
                stream_copy_to_stream(
                    $storage->readStream($file->getPath()),
                    fopen('php://output', 'w')
                );
            }
        );

        $this->addContentTypeHeader($response, $file);
        $this->addContentDispositionHeader($response, $request, $file);

        return $response;
    }

    private function generate(string $route, array $params): string
    {
        $uri = $this->router->generate($route, [], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->signer->sign($uri.'?'.http_build_query(array_filter($params)));
    }

    private function getFile(Request $request, VirtualFilesystemInterface $storage): FilesystemItem|null
    {
        $path = $request->query->get(self::PARAM_PATH, '');

        try {
            $file = $storage->get($path, VirtualFilesystemInterface::BYPASS_DBAFS);
        } catch (VirtualFilesystemException $e) {
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
