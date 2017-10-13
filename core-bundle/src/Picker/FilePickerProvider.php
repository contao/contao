<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Picker;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\Validator;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FilePickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @var string
     */
    private $uploadPath;

    /**
     * @param FactoryInterface    $menuFactory
     * @param RouterInterface     $router
     * @param TranslatorInterface $translator
     * @param string              $uploadPath
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, TranslatorInterface $translator, string $uploadPath)
    {
        parent::__construct($menuFactory, $router, $translator);

        $this->uploadPath = $uploadPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'filePicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context): bool
    {
        return \in_array($context, ['file', 'link'], true) && $this->getUser()->hasAccess('files', 'modules');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config): bool
    {
        if ('file' === $config->getContext()) {
            return Validator::isUuid($config->getValue());
        }

        return false !== strpos($config->getValue(), '{{file::')
            || 0 === strpos($config->getValue(), $this->uploadPath)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable(): string
    {
        return 'tl_files';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config): array
    {
        $value = $config->getValue();

        if ('file' === $config->getContext()) {
            $attributes = array_intersect_key(
                $config->getExtras(),
                array_flip(['fieldType', 'files', 'filesOnly', 'path', 'extensions'])
            );

            if (!isset($attributes['fieldType'])) {
                $attributes['fieldType'] = 'radio';
            }

            if ($value) {
                $attributes['value'] = [];

                foreach (explode(',', $value) as $v) {
                    $attributes['value'][] = $this->urlEncode($this->convertValueToPath($v));
                }
            }

            return $attributes;
        }

        $attributes = [
            'fieldType' => 'radio',
            'filesOnly' => true,
        ];

        if ($value) {
            if (false !== strpos($value, '{{file::')) {
                $value = str_replace(['{{file::', '}}'], '', $value);
            }

            if (0 === strpos($value, $this->uploadPath.'/')) {
                $attributes['value'] = $this->urlEncode($value);
            } else {
                $attributes['value'] = $this->urlEncode($this->convertValueToPath($value));
            }
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value): string
    {
        if ('file' === $config->getContext()) {
            return $value;
        }

        /** @var FilesModel $filesAdapter */
        $filesAdapter = $this->framework->getAdapter(FilesModel::class);
        $filesModel = $filesAdapter->findByPath(rawurldecode($value));

        if ($filesModel instanceof FilesModel) {
            return '{{file::'.StringUtil::binToUuid($filesModel->uuid).'}}';
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null): array
    {
        return ['do' => 'files'];
    }

    /**
     * Converts the UUID value to a file path if possible.
     *
     * @param string $value
     *
     * @return string
     */
    private function convertValueToPath(string $value): string
    {
        /** @var FilesModel $filesAdapter */
        $filesAdapter = $this->framework->getAdapter(FilesModel::class);

        if (Validator::isUuid($value) && ($filesModel = $filesAdapter->findByUuid($value)) instanceof FilesModel) {
            return $filesModel->path;
        }

        return $value;
    }

    /**
     * Urlencodes a file path preserving slashes.
     *
     * @param string $strPath
     *
     * @return string
     *
     * @see \Contao\System::urlEncode()
     */
    private function urlEncode(string $strPath): string
    {
        return str_replace('%2F', '/', rawurlencode($strPath));
    }
}
