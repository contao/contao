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

use Contao\CoreBundle\Filesystem\PublicUri\TemporaryAccessOption;
use Contao\StringUtil;
use Nyholm\Psr7\Uri;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\RouterInterface;

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
    /**
     * @deprecated can be removed as soon as the deprecated methods are removed
     */
    private const PARAM_PATH = 'p';

    private const PARAM_CONTEXT = 'ctx';

    private const PARAM_DISPOSITION = 'd';

    private const PARAM_FILE_NAME = 'f';

    private const TOKEN_PARAM = 't';

    public function __construct(
        private readonly UriSigner $signer,
        private readonly RouterInterface $router,
        private readonly Security $security,
        private readonly MountManager $mountManager,
    ) {
    }

    /**
     * @deprecated Deprecated since Contao 6.0, to be removed in Contao 7;
     * *              use "generateUrl()" instead.
     * *
     * Generate a signed file URL that a browser will display inline.
     *
     * You can optionally provide an array of $context, that will also be incorporated
     * into the URL.
     */
    public function generateInlineUrl(string $url, string $path, array|null $context = null): string
    {
        trigger_deprecation('contao/core-bundle', '6.0', 'The "generateInlineUrl()" method is deprecated. Use "generateUrl()" instead.');

        return $this->generate($url, [
            self::PARAM_PATH => $path,
            self::PARAM_CONTEXT => null !== $context ? serialize($context) : null,
        ]);
    }

    /**
     * Generate a signed file URL that a browser will download.
     */
    public function generateUrl(string $path, TemporaryAccessOption $temporaryAccessOption, string|null $fileName = null, string $disposition = HeaderUtils::DISPOSITION_INLINE): string
    {
        if (!\in_array($disposition, [HeaderUtils::DISPOSITION_ATTACHMENT, HeaderUtils::DISPOSITION_INLINE], true)) {
            throw new \InvalidArgumentException(\sprintf('The disposition must be either "%s" or "%s".', HeaderUtils::DISPOSITION_ATTACHMENT, HeaderUtils::DISPOSITION_INLINE));
        }

        $parameters = [
            'path' => $path,
            self::PARAM_DISPOSITION => $disposition,
            self::PARAM_CONTEXT => $temporaryAccessOption->getContentHash(), // Invalidates the URLs when the content changes
        ];

        if (null !== $fileName) {
            // Call makeDisposition() here to check if the file name is valid
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName, 'f');
            $parameters[self::TOKEN_PARAM] = $fileName;
        }

        // When user is logged in, ensure the URL is private
        $tokenHash = $this->getSecurityTokenHash();

        if (null !== $tokenHash) {
            $parameters[self::TOKEN_PARAM] = $tokenHash;
        }

        $url = $this->router->generate('contao_file_stream', $parameters, RouterInterface::ABSOLUTE_URL);

        return $this->signer->sign($url, $temporaryAccessOption->getTtl());
    }

    /**
     * @deprecated Deprecated since Contao 6.0, to be removed in Contao 7;
     *              use "generateUrl()" instead.
     *
     * Generate a signed file URL that a browser will download.
     *
     * You can optionally provide an array of $context, that will also be incorporated
     * into the URL.
     */
    public function generateDownloadUrl(string $url, string $path, string|null $fileName = null, array|null $context = null): string
    {
        trigger_deprecation('contao/core-bundle', '6.0', 'The "generateDownloadUrl()" method is deprecated. Use "generateUrl()" instead.');

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

    public function handleRequest(Request $request, string $path): Response
    {
        if (!$this->signer->checkRequest($request)) {
            return new Response('The provided file URL is not valid.', Response::HTTP_FORBIDDEN);
        }

        // Token does not match
        if ($this->getSecurityTokenHash() !== $request->query->get('token')) {
            return new Response('The provided token is not valid.', Response::HTTP_FORBIDDEN);
        }

        $file = $this->mountManager->get($path);

        if (!$file) {
            return new Response('The requested resource does not exist.', Response::HTTP_NOT_FOUND);
        }

        return $this->generateResponse($this->mountManager->readStream($path), $request, $file);
    }

    /**
     * @deprecated Deprecated since Contao 6.0, to be removed in Contao 7;
     *             use "handleRequest()" instead.
     *
     * Handle download request and stream file contents.
     *
     * If you need to add custom logic, you can implement the $onProcess closure that
     * gets called with a FilesystemItem object and the context defined when
     * generating the URL. You can shortcut operation by returning your own response
     * there, otherwise return null.
     *
     * @param (\Closure(FilesystemItem, array): (Response|null))|null $onProcess
     */
    public function handle(Request $request, VirtualFilesystemInterface $storage, \Closure|null $onProcess = null): Response
    {
        trigger_deprecation('contao/core-bundle', '6.0', 'The "handle()" method is deprecated. Use "handleRequest()" instead.');

        if (!$this->signer->checkRequest($request)) {
            return new Response('The provided file URL is not valid.', Response::HTTP_FORBIDDEN);
        }

        if (!$file = $this->getFile($request, $storage)) {
            return new Response('The requested resource does not exist.', Response::HTTP_NOT_FOUND);
        }

        if ($onProcess) {
            $context = StringUtil::deserialize($request->query->get(self::PARAM_CONTEXT, ''), true);
            $response = $onProcess($file, $context);

            if ($response instanceof Response) {
                return $response;
            }
        }

        return $this->generateResponse($storage->readStream($file->getPath()), $request, $file);
    }

    private function getSecurityTokenHash(): string|null
    {
        $token = $this->security->getToken();

        if (!$token) {
            return null;
        }

        return hash('xxh3', serialize($token));
    }

    /**
     * @param resource $stream
     */
    private function generateResponse($stream, Request $request, FilesystemItem $file): Response
    {
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
        $uri = new Uri($url);
        parse_str($uri->getQuery(), $existingParams);
        $params = [...$existingParams, ...array_filter($params)];

        // Unset default uri_signer parameters (#7989)
        unset($params['_hash'], $params['_expiration']);

        return $this->signer->sign((string) $uri->withQuery(http_build_query($params)));
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
        $response->headers->set('Content-Type', $file->getMimeType('application/octet-stream'));
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
