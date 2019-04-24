<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Picker;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Picker\AbstractPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\NewsArchiveModel;
use Contao\NewsModel;

class NewsPickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    protected const DEFAULT_INSERTTAG = '{{news_url::%s}}';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'newsPicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context): bool
    {
        return 'link' === $context && $this->getUser()->hasAccess('news', 'modules');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config): bool
    {
        $insertTagChunks = explode('%s', $this->getInsertTag($config, self::DEFAULT_INSERTTAG), 2);

        return false !== strpos($config->getValue(), $insertTagChunks[0]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable(): string
    {
        return 'tl_news';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        if ($source = $config->getExtra('source')) {
            $attributes['preserveRecord'] = $source;
        }

        if ($this->supportsValue($config)) {
            $insertTagChunks = explode('%s', $this->getInsertTag($config, self::DEFAULT_INSERTTAG), 2);

            $attributes['value'] = str_replace($insertTagChunks, '',  $config->getValue());
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value): string
    {
        return sprintf($this->getInsertTag($config, self::DEFAULT_INSERTTAG), $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null): array
    {
        $params = ['do' => 'news'];

        if (null === $config || !$config->getValue()) {
            return $params;
        }

        $insertTagChunks = explode('%s', $this->getInsertTag($config, self::DEFAULT_INSERTTAG), 2);

        if (false === strpos($config->getValue(), $insertTagChunks[0])) {
            return $params;
        }

        $value = str_replace($insertTagChunks, '', $config->getValue());

        if (null !== ($newsArchiveId = $this->getNewsArchiveId($value))) {
            $params['table'] = 'tl_news';
            $params['id'] = $newsArchiveId;
        }

        return $params;
    }

    /**
     * @param int|string $id
     */
    private function getNewsArchiveId($id): ?int
    {
        /** @var NewsModel $newsAdapter */
        $newsAdapter = $this->framework->getAdapter(NewsModel::class);

        if (!($newsModel = $newsAdapter->findById($id)) instanceof NewsModel) {
            return null;
        }

        if (!($newsArchive = $newsModel->getRelated('pid')) instanceof NewsArchiveModel) {
            return null;
        }

        return (int) $newsArchive->id;
    }
}
