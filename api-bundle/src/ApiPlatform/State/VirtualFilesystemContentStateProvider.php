<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Contao\CoreBundle\Filesystem\PermissionCheckingVirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemException;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<Response>
 */
final class VirtualFilesystemContentStateProvider implements ProviderInterface
{
    private readonly VirtualFilesystemInterface $filesStorage;

    public function __construct(VirtualFilesystem $filesStorage, Security $security)
    {
        $this->filesStorage = new PermissionCheckingVirtualFilesystem($filesStorage, $security);
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        $path = $uriVariables['path'] ?? null;
        $item = \is_string($path) ? $this->filesStorage->get($path) : null;

        if (!$item?->isFile()) {
            throw new NotFoundHttpException('The requested file does not exist.');
        }

        $request = $context['request'] ?? null;

        if ($request instanceof Request && $request->isMethod('HEAD')) {
            $response = new Response();
        } else {
            $response = new StreamedResponse(
                function () use ($path): void {
                    $stream = $this->filesStorage->readStream($path);

                    try {
                        fpassthru($stream);
                    } finally {
                        fclose($stream);
                    }
                },
            );
        }

        $fileName = $item->getName();

        if (1 !== preg_match('//u', $fileName)) {
            throw VirtualFilesystemException::encounteredInvalidPath($item->getPath());
        }

        $fileNameFallback = preg_replace('/[^\x20-\x7e]+|%/', '_', $fileName);

        $response->headers->set('Content-Type', $item->getMimeType('application/octet-stream'));
        $response->headers->set('Content-Length', (string) $item->getFileSize());
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName, $fileNameFallback));
        $response->headers->set('Accept-Ranges', 'none');
        $response->headers->set('Cache-Control', 'no-cache, private');

        if (null !== ($lastModified = $item->getLastModified())) {
            $response->setLastModified(new \DateTimeImmutable('@'.$lastModified));
        }

        if ($request instanceof Request) {
            $response->isNotModified($request);
        }

        return $response;
    }
}
