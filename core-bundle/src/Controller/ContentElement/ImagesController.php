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
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\FilesystemUtil;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class ImagesController extends AbstractContentElementController
{
    public function __construct(private Security $security, private VirtualFilesystemInterface $filesStorage, private Studio $studio)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        // Find and sort filesystem items
        $filesystemItems = $this->getFilesystemItems($model);
        // todo: optionally skip if there is no metadata

        if (\in_array(($sortBy = $model->sortBy), FilesystemItemIterator::$supportedSortingModes, true)) {
            $filesystemItems = $filesystemItems->sort($sortBy);
        }

        // Compile images
        $figureBuilder = $this->studio
            ->createFigureBuilder()
            ->setSize($model->size)
            ->setLightboxGroupIdentifier('lb'.$model->id)
            ->enableLightbox((bool) $model->fullsize)
        ;

        if ('image' === $model->type) {
            $figureBuilder->setMetadata($model->getOverwriteMetadata());
        }

        $images = array_map(
            static fn (FilesystemItem $filesystemItem): Figure => $figureBuilder
                // todo: we should not need public paths as the source to create images but stream from the VFS directly
                ->fromPath($filesystemItem->getPublicPath())
                ->build(),
            iterator_to_array($filesystemItems)
        );

        // todo: limit + pagination

        $template->set('images', $images);
        $template->set('sorting', $sortBy);

        return $template->getResponse();
    }

    private function getFilesystemItems(ContentModel $model): FilesystemItemIterator
    {
        $sources = match (true) {
            'image' === $model->type => [$model->singleSRC],
            $model->useHomeDir && ($user = $this->security->getUser()) instanceof FrontendUser && $user->assignDir && ($homeDir = $user->homeDir) => $homeDir,
            default => $model->multiSRC,
        };

        return FilesystemUtil::listContentsFromMultiSRC($this->filesStorage, $sources);
    }
}
