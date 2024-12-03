<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Backend\Provider;

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\PermissionCheckingVirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @experimental
 */
class FilesStorageProvider implements ProviderInterface
{
    public const TYPE = 'contao.vfs.files';

    private readonly PermissionCheckingVirtualFilesystem $permissionCheckingFilesStorage;

    public function __construct(
        private readonly VirtualFilesystem $filesStorage,
        Security $security,
        private readonly Studio $studio,
    ) {
        $this->permissionCheckingFilesStorage = new PermissionCheckingVirtualFilesystem(
            $this->filesStorage, $security,
        );
    }

    public function supportsType(string $type): bool
    {
        return self::TYPE === $type;
    }

    /**
     * @return iterable<Document>
     */
    public function updateIndex(ReindexConfig $config): iterable
    {
        $items = $this->filesStorage
            ->listContents('', true)
            ->files()
        ;

        if (null !== ($lastIndexed = $config->getUpdateSince()?->getTimestamp())) {
            $items = $items->filter(static fn (FilesystemItem $item): bool => ($item->getLastModified() ?? 0) > $lastIndexed);
        }

        foreach ($items as $item) {
            $document = new Document(
                $item->getPath(),
                self::TYPE,
                $item->getName(),
            );

            if ($item->isFile()) {
                $document = $document->withTags(['extension:'.$item->getExtension(true)]);
            }

            yield $document->withMetadata([
                'path' => $item->getPath(),
            ]);
        }
    }

    public function convertDocumentToHit(Document $document): Hit|null
    {
        $metadata = $document->getMetadata();

        if (!($item = $this->filesStorage->get($metadata['path']))) {
            return null;
        }

        // TODO: service for view and edit URLs
        $viewUrl = 'https://todo.com?view='.$document->getId();
        $editUrl = 'https://todo.com?edit='.$document->getId();

        $hit = (new Hit($document, $item->getName(), $viewUrl))
            ->withEditUrl($editUrl)
            ->withContext($document->getSearchableContent())
            ->withMetadata([
                'filesystem_item' => $item,
            ])
        ;

        if ($item->isImage()) {
            $figureBuilder = $this->studio
                ->createFigureBuilder()
                ->fromStorage($this->filesStorage, $item->getPath())
                ->disableMetadata()
            ;

            $hit = $hit->withImageFigureBuilder($figureBuilder);
        }

        return $hit;
    }

    public function isHitGranted(TokenInterface $token, Hit $hit): bool
    {
        return $this->permissionCheckingFilesStorage->canAccessLocation(
            $hit->getDocument()->getMetadata()['path'] ?? '',
        );
    }
}
