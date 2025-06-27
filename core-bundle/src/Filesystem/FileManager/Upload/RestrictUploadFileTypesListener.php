<?php
declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem\FileManager\Upload;

use Contao\Config;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[AsEventListener]
class RestrictUploadFileTypesListener
{
    private array $allowedUploadTypes;

    public function __construct(private readonly TranslatorInterface $translator, array|null $allowedUploadTypes = null)
    {
        $this->allowedUploadTypes = $allowedUploadTypes ?? explode(',', Config::get('uploadTypes'));
    }

    public function __invoke(UploadFileEvent $event): void
    {
        if (!\in_array($event->getExtension(), $this->allowedUploadTypes, true)) {
            $reason = $this->translator->trans(
                'message.view_upload.error_upload_type',
                [sprintf('<span class="token">.%s</span>', $event->getExtension())],
                'contao_file_manager'
            );

            $event->deny($reason);
        }
    }
}
