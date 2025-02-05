<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

use Contao\CoreBundle\DependencyInjection\Attribute\AsPickerProvider;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Knp\Menu\FactoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsPickerProvider(priority: 160)]
class FilePickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @internal
     */
    public function __construct(
        FactoryInterface $menuFactory,
        RouterInterface $router,
        TranslatorInterface $translator,
        private readonly Security $security,
        private readonly string $uploadPath,
    ) {
        parent::__construct($menuFactory, $router, $translator);
    }

    public function getName(): string
    {
        return 'filePicker';
    }

    public function supportsContext(string $context): bool
    {
        return \in_array($context, ['file', 'link'], true) && $this->security->isGranted('contao_user.modules', 'files');
    }

    public function supportsValue(PickerConfig $config): bool
    {
        if ('file' === $config->getContext()) {
            return Validator::isUuid($config->getValue());
        }

        return $this->isMatchingInsertTag($config) || Path::isBasePath($this->uploadPath, $config->getValue());
    }

    public function getDcaTable(PickerConfig|null $config = null): string
    {
        return 'tl_files';
    }

    public function getDcaAttributes(PickerConfig $config): array
    {
        if ('file' === $config->getContext()) {
            return $this->getFileDcaAttributes($config);
        }

        return $this->getLinkDcaAttributes($config);
    }

    public function convertDcaValue(PickerConfig $config, mixed $value): int|string
    {
        if ('file' === $config->getContext()) {
            return $value;
        }

        $filesAdapter = $this->framework->getAdapter(FilesModel::class);

        if ($filesModel = $filesAdapter->findByPath(rawurldecode($value))) {
            return \sprintf($this->getInsertTag($config), StringUtil::binToUuid($filesModel->uuid));
        }

        return $value;
    }

    protected function getRouteParameters(PickerConfig|null $config = null): array
    {
        return ['do' => 'files'];
    }

    protected function getDefaultInsertTag(): string
    {
        return '{{file::%s}}';
    }

    /**
     * Converts the UUID value to a file path if possible.
     */
    private function convertValueToPath(string $value): string
    {
        if (!Validator::isUuid($value)) {
            return $value;
        }

        $filesAdapter = $this->framework->getAdapter(FilesModel::class);

        if ($filesModel = $filesAdapter->findByUuid($value)) {
            return $filesModel->path;
        }

        return $value;
    }

    /**
     * Urlencodes a file path preserving slashes.
     *
     * @see System::urlEncode()
     */
    private function urlEncode(string $strPath): string
    {
        return str_replace('%2F', '/', rawurlencode($strPath));
    }

    /**
     * @return array<string, string|bool>
     */
    private function getFileDcaAttributes(PickerConfig $config): array
    {
        $attributes = array_intersect_key(
            $config->getExtras(),
            array_flip(['fieldType', 'files', 'filesOnly', 'path', 'extensions']),
        );

        if (!isset($attributes['fieldType'])) {
            $attributes['fieldType'] = 'radio';
        }

        $value = $config->getValue();

        if ($value) {
            $attributes['value'] = [];

            foreach (explode(',', $value) as $v) {
                $attributes['value'][] = $this->urlEncode($this->convertValueToPath($v));
            }
        }

        return $attributes;
    }

    /**
     * @return array<string, array|string|bool>
     */
    private function getLinkDcaAttributes(PickerConfig $config): array
    {
        $attributes = [
            'fieldType' => 'radio',
            'filesOnly' => true,
        ];

        $value = $config->getValue();

        if ($value) {
            if ($this->isMatchingInsertTag($config)) {
                $value = $this->getInsertTagValue($config);
            }

            if (Path::isBasePath($this->uploadPath, $value)) {
                $attributes['value'] = $this->urlEncode($value);
            } else {
                $attributes['value'] = $this->urlEncode($this->convertValueToPath($value));
            }

            if ($flags = $this->getInsertTagFlags($config)) {
                $attributes['flags'] = $flags;
            }
        }

        return $attributes;
    }
}
