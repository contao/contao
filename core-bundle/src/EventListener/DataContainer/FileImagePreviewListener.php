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

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\File;
use Contao\Image\ResizeConfiguration;
use Contao\Message;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;

#[AsCallback(table: 'tl_files', target: 'fields.preview.input_field')]
class FileImagePreviewListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly ImageFactoryInterface $imageFactory,
        private readonly string $projectDir,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(DataContainer $dc): string
    {
        try {
            $objFile = new File($dc->id);
        } catch (\Exception) {
            return '';
        }

        if (!$objFile->isImage || ($objFile->isSvgImage && (!$objFile->viewWidth || !$objFile->viewHeight))) {
            return '';
        }

        if (
            !$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_files::importantPartX')
            || !$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_files::importantPartY')
            || !$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_files::importantPartWidth')
            || !$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_files::importantPartHeight')
        ) {
            return '';
        }

        try {
            $image = rawurldecode($this->imageFactory->create($this->projectDir.'/'.$objFile->path, [699, 524, ResizeConfiguration::MODE_BOX])->getUrl($this->projectDir));
        } catch (\Exception $e) {
            Message::addError($e->getMessage());

            return '';
        }

        $objImage = new File($image);

        // Get the input field suffix (edit multiple mode)
        $suffix = substr($dc->inputName, \strlen($dc->field));

        return $this->twig->render('@Contao/backend/file_manager/image_preview.html.twig', [
            'image' => [
                'src' => $objImage->dataUri,
                'width' => $objImage->width,
                'height' => $objImage->height,
            ],
            'fields' => [
                'x' => 'ctrl_importantPartX'.$suffix,
                'y' => 'ctrl_importantPartY'.$suffix,
                'width' => 'ctrl_importantPartWidth'.$suffix,
                'height' => 'ctrl_importantPartHeight'.$suffix,
            ],
            'show_help' => (bool) $this->framework->getAdapter(Config::class)->get('showHelp'),
        ]);
    }
}
