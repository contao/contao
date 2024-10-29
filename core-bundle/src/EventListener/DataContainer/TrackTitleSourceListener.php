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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Message;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

#[AsCallback(table: 'tl_content', target: 'fields.textTrackSRC.save')]
readonly class TrackTitleSourceListener
{
    public function __construct(
        private ContaoFramework $framework,
        private VirtualFilesystem $filesStorage,
        private RequestStack $requestStack,
    ) {
    }

    public function __invoke(mixed $value, DataContainer $dc): mixed
    {
        $invalid = [];

        if (null !== $value) {
            $fileSystemItems = FilesystemUtil::listContentsFromSerialized($this->filesStorage, $value);

            foreach ($fileSystemItems as $fileSystemItem) {
                $extraMetadata = $fileSystemItem->getExtraMetadata();

                if (
                    null === $extraMetadata->getTextTrack()?->getSourceLanguage()
                    || '' === $extraMetadata->getLocalized()?->getFirst()?->getTitle()
                ) {
                    $invalid[] = $fileSystemItem->getName();
                }
            }
        }

        $message = $this->framework->getAdapter(Message::class);

        if ([] !== $invalid) {
            $message->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['textTrackMetadataMissing'], implode(', ', $invalid)));
        } elseif ($message->hasError()) {
            // ToDo: Resets the message - There is currently no possible way to show messages
            // for just the current request
            $message->reset();
        }

        return $value;
    }
}
