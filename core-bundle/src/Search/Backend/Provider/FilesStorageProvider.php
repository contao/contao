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
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @experimental
 */
class FilesStorageProvider implements ProviderInterface, TagProvidingProviderInterface
{
    public const TYPE = 'contao.vfs.files';

    private readonly PermissionCheckingVirtualFilesystem $permissionCheckingFilesStorage;

    public function __construct(
        private readonly VirtualFilesystem $filesStorage,
        Security $security,
        private readonly Studio $studio,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly string $uploadPath,
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
        // Limited to certain document ids but not of our type - skip.
        if ($config->isLimitedToDocumentIdsExcludingType(self::TYPE)) {
            return [];
        }

        $items = $this->filesStorage
            ->listContents('', true)
            ->files()
        ;

        if ($documentIds = $config->getLimitedDocumentIds()->getDocumentIdsForType(self::TYPE)) {
            $items = $items->filter(static fn (FilesystemItem $item): bool => \in_array($item->getPath(), $documentIds, true));
        }

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

        $parentFolder = \dirname($document->getId()) ?: '.';

        $viewUrl = $this->router->generate('contao_backend', [
            'do' => 'files',
            'fn' => '.' === $parentFolder ? '' : "$this->uploadPath/$parentFolder",
        ]);

        $editUrl = $this->router->generate('contao_backend', [
            'do' => 'files',
            'id' => $this->uploadPath.'/'.$document->getId(),
            'act' => 'edit',
        ]);

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

    public function isDocumentGranted(TokenInterface $token, Document $document): bool
    {
        return $this->permissionCheckingFilesStorage->canAccessLocation(
            $document->getMetadata()['path'] ?? '',
        );
    }

    public function convertTypeToVisibleType(string $type): string
    {
        return $this->translator->trans('MSC.file', [], 'contao_default');
    }

    public function getFacetLabelForTag(string $tag): string
    {
        if (!str_starts_with($tag, 'extension:')) {
            return $tag;
        }

        return substr($tag, 10);
    }
}
