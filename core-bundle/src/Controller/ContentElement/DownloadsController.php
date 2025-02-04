<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\FilesystemUtil;
use Contao\CoreBundle\Filesystem\SortMode;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement('download', category: 'files')]
#[AsContentElement('downloads', category: 'files')]
class DownloadsController extends AbstractDownloadContentElementController
{
    public function __construct(
        private readonly Security $security,
        private readonly VirtualFilesystem $filesStorage,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $filesystemItems = $this->getFilesystemItems($request, $model);

        // Sort elements; relay to client-side logic if list should be randomized
        if ($sortMode = SortMode::tryFrom($model->sortBy)) {
            $filesystemItems = $filesystemItems->sort($sortMode);
        }

        $template->set('sort_mode', $sortMode);
        $template->set('randomize_order', 'random' === $model->sortBy);

        $downloads = $this->compileDownloadsList($filesystemItems, $model, $request);

        // Explicitly define title/text metadata for a single file
        if ('download' === $model->type && $model->overwriteLink && $downloads) {
            $downloads[0]['title'] = $model->titleText;
            $downloads[0]['text'] = $model->linkTitle;
        }

        $template->set('downloads', $downloads);

        return $template->getResponse();
    }

    /**
     * Retrieve selected filesystem items but filter out those, that do not match the
     * current DCA and configuration constraints.
     */
    protected function getFilesystemItems(Request $request, ContentModel $model): FilesystemItemIterator
    {
        $homeDir = null;

        if ($model->useHomeDir) {
            $user = $this->security->getUser();

            if ($user instanceof FrontendUser && $user->assignDir && $user->homeDir) {
                $homeDir = $user->homeDir;
            }
        }

        $sources = match (true) {
            'download' === $model->type => [$model->singleSRC],
            default => $homeDir ?: $model->multiSRC,
        };

        // Find filesystem items
        $filesystemItems = FilesystemUtil::listContentsFromSerialized($this->filesStorage, $sources);

        // Optionally filter out files without metadata
        if ('downloads' === $model->type && $model->metaIgnore) {
            $filesystemItems = $filesystemItems->filter(
                static fn (FilesystemItem $item): bool => (bool) $item->getExtraMetadata()->getLocalized()?->getDefault(),
            );
        }

        return $this->applyDownloadableFileExtensionsFilter($filesystemItems);
    }

    protected function getVirtualFilesystem(): VirtualFilesystemInterface
    {
        return $this->filesStorage;
    }
}
