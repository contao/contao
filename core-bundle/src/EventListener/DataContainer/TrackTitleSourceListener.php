<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Filesystem\FilesystemUtil;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\DataContainer;
use Contao\Message;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

#[AsCallback(table: 'tl_content', target: 'fields.textTrackSRC.load')]
#[AsCallback(table: 'tl_content', target: 'fields.textTrackSRC.save')]
readonly class TrackTitleSourceListener
{
    public function __construct(
        private VirtualFilesystem $filesStorage,
        private RequestStack $requestStack,
    ) {
    }

    public function __invoke(mixed $value, DataContainer $dc): mixed
    {
        $fileSystemItems = FilesystemUtil::listContentsFromSerialized($this->filesStorage, $value);

        $invalid = [];

        foreach ($fileSystemItems as $fileSystemItem) {
            $extraMetadata = $fileSystemItem->getExtraMetadata();

            /**
             * @todo change to:
             *    $extraMetadata->getTextTrack();
             *    $extraMetadata->getLocalized()?->getDefault()?->getTitle();
             */
            if (
                null === $extraMetadata['textTrack']?->getSourceLanguage()
                || empty(($extraMetadata['metadata'] ?? null)?->getFirst()?->getTitle())
            ) {
                $invalid[] = $fileSystemItem->getName();
            }
        }

        if ([] !== $invalid) {
            Message::addError(\sprintf($GLOBALS['TL_LANG']['ERR']['textTrackMetadataMissing'], implode(', ', $invalid)));
        } elseif (Message::hasError()) {
            $session = $this->requestStack->getCurrentRequest()?->getSession();

            // Reset the error if it exists
            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->get('contao.BE.error');
            }
        }

        return $value;
    }
}
