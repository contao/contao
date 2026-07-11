<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Job;

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Translation\TranslatableMessage;

class Attachment
{
    public function __construct(
        private readonly FilesystemItem $filesystemItem,
        private readonly TranslatableMessage $fileLabel,
        private readonly string $downloadUrl,
    ) {
    }

    public function getFilesystemItem(): FilesystemItem
    {
        return $this->filesystemItem;
    }

    public function getFileName(): string
    {
        return $this->getFilesystemItem()->getName();
    }

    public function getFileLabel(): TranslatableMessage
    {
        return $this->fileLabel;
    }

    public function getDownloadUrl(): string
    {
        return $this->downloadUrl;
    }

    public function toStreamedResponse(): StreamedResponse
    {
        $stream = $this->filesystemItem->getStorage()->readStream($this->filesystemItem->getPath());

        $response = new StreamedResponse(
            static function () use ($stream): void {
                stream_copy_to_stream($stream, fopen('php://output', 'w'));
            },
        );

        $response->headers->set('Content-Type', $this->filesystemItem->getMimeType('application/octet-stream'));

        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $this->filesystemItem->getName()),
        );

        return $response;
    }
}
