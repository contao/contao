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
use Symfony\Bundle\SecurityBundle\Security;

#[AsCallback(table: 'tl_files', target: 'fields.preview.input_field')]
class FileImagePreviewListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly ImageFactoryInterface $imageFactory,
        private readonly string $projectDir,
    ) {
    }

    public function __invoke(DataContainer $dc): string
    {
        $objFile = new File($dc->id);

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
        } catch (\Exception) {
            return '';
        }

        $objImage = new File($image);
        $ctrl = 'ctrl_preview_'.substr(md5($image), 0, 8);

        $strPreview = '
<div id="'.$ctrl.'" class="tl_edit_preview">
<img src="'.$objImage->dataUri.'" width="'.$objImage->width.'" height="'.$objImage->height.'" alt="">
</div>';

        // Add the script to mark the important part
        $strPreview .= '<script>Backend.editPreviewWizard($(\''.$ctrl.'\'));</script>';

        if ($this->framework->getAdapter(Config::class)->get('showHelp')) {
            $strPreview .= '<p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['tl_files']['preview'][1].'</p>';
        }

        $strPreview = '<div class="widget">'.$strPreview.'</div>';

        return $strPreview;
    }
}
