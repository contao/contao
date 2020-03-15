<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Helper;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\Image\PictureConfiguration;
use Contao\Validator;
use Symfony\Component\HttpFoundation\RequestStack;

class ImageHelper
{
    /** @var PictureFactoryInterface */
    private $pictureFactory;

    /** @var string */
    private $rootDir;

    /** @var string */
    private $staticUrl;

    /** @var string */
    private $locale;

    /** @var ContaoFramework */
    private $framework;

    public function __construct(PictureFactoryInterface $pictureFactory, string $rootDir, ContaoContext $fileContext, RequestStack $requestStack, ContaoFramework $framework)
    {
        $this->pictureFactory = $pictureFactory;
        $this->rootDir = $rootDir;
        $this->staticUrl = $fileContext->getStaticUrl();
        $this->framework = $framework;

        $request = $requestStack->getCurrentRequest();
        $this->locale = null !== $request ? $request->getLocale() : '';
    }

    /**
     * @param string                                     $fileIdentifier    can be a file's uuid or id or path
     * @param int|string|array|PictureConfiguration|null $sizeConfiguration
     */
    public function createPicture(string $fileIdentifier, $sizeConfiguration): Picture
    {
        $file = $this->getFile($fileIdentifier);

        if (null === $file || 'file' !== $file->type) {
            throw new \InvalidArgumentException("Could not retrieve file for identifier '$fileIdentifier'");
        }

        $picture = $this->pictureFactory->create(
            $this->rootDir.'/'.$file->path,
            $sizeConfiguration
        );

        return new Picture(
            $picture->getImg($this->rootDir, $this->staticUrl),
            $picture->getSources($this->rootDir, $this->staticUrl),
            $this->getAltAttribute($file)
        );
    }

    public function createImage(string $fileIdentifier, $sizeConfiguration): Image
    {
        // todo
        throw new \RuntimeException('not implemented');
    }

    private function getFile(string $uuidOrIdOrPath): ?FilesModel
    {
        /** @var Validator $validator */
        $validator = $this->framework->getAdapter(Validator::class);

        /** @var FilesModel $filesModel */
        $filesModel = $this->framework->getAdapter(FilesModel::class);

        if ($validator->isUuid($uuidOrIdOrPath)) {
            return $filesModel->findByUuid($uuidOrIdOrPath);
        }

        if (is_numeric($uuidOrIdOrPath)) {
            return $filesModel->findById($uuidOrIdOrPath);
        }

        return $filesModel->findByPath($uuidOrIdOrPath);
    }

    private function getAltAttribute(FilesModel $file): ?string
    {
        /** @var Frontend $frontend */
        $frontend = $this->framework->getAdapter(Frontend::class);

        $metaData = $frontend->getMetaData($file->meta, $this->locale);

        return $metaData['alt'] ?? null;
    }
}
