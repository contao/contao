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

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\Validator;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class FilePickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var string
     */
    private $uploadPath;

    /**
     * @internal Do not inherit from this class; decorate the "contao.picker.file_provider" service instead
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, TranslatorInterface $translator, Security $security, string $uploadPath)
    {
        parent::__construct($menuFactory, $router, $translator);

        $this->security = $security;
        $this->uploadPath = $uploadPath;
    }

    public function getName(): string
    {
        return 'filePicker';
    }

    public function supportsContext($context): bool
    {
        return \in_array($context, ['file', 'link'], true) && $this->security->isGranted('contao_user.modules', 'files');
    }

    public function supportsValue(PickerConfig $config): bool
    {
        if ('file' === $config->getContext()) {
            return Validator::isUuid($config->getValue());
        }

        return $this->isMatchingInsertTag($config) || 0 === strpos($config->getValue(), $this->uploadPath.'/');
    }

    public function getDcaTable(): string
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

    public function convertDcaValue(PickerConfig $config, $value): string
    {
        if ('file' === $config->getContext()) {
            return $value;
        }

        /** @var FilesModel $filesAdapter */
        $filesAdapter = $this->framework->getAdapter(FilesModel::class);
        $filesModel = $filesAdapter->findByPath(rawurldecode($value));

        if ($filesModel instanceof FilesModel) {
            return sprintf($this->getInsertTag($config), StringUtil::binToUuid($filesModel->uuid));
        }

        return $value;
    }

    protected function getRouteParameters(PickerConfig $config = null): array
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
     * @see \Contao\System::urlEncode()
     */
    private function urlEncode(string $strPath): string
    {
        return str_replace('%2F', '/', rawurlencode($strPath));
    }

    /**
     * @return array<string,string|bool>
     */
    private function getFileDcaAttributes(PickerConfig $config): array
    {
        $attributes = array_intersect_key(
            $config->getExtras(),
            array_flip(['fieldType', 'files', 'filesOnly', 'path', 'extensions'])
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
     * @return array<string,string|bool>
     */
    private function getLinkDcaAttributes(PickerConfig $config): array
    {
        $attributes = [
            'fieldType' => 'radio',
            'filesOnly' => true,
        ];

        $value = $config->getValue();

        if ($value) {
            $chunks = $this->getInsertTagChunks($config);

            if (false !== strpos($value, $chunks[0])) {
                $value = str_replace($chunks, '', $value);
            }

            if (0 === strpos($value, $this->uploadPath.'/')) {
                $attributes['value'] = $this->urlEncode($value);
            } else {
                $attributes['value'] = $this->urlEncode($this->convertValueToPath($value));
            }
        }

        return $attributes;
    }
}
