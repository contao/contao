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

use Knp\Menu\FactoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ArticlePickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface
{
    /**
     * @internal
     */
    public function __construct(
        FactoryInterface $menuFactory,
        RouterInterface $router,
        TranslatorInterface $translator,
        private readonly Security $security,
    ) {
        parent::__construct($menuFactory, $router, $translator);
    }

    public function getName(): string
    {
        return 'articlePicker';
    }

    public function supportsContext(string $context): bool
    {
        return 'link' === $context && $this->security->isGranted('contao_user.modules', 'article');
    }

    public function supportsValue(PickerConfig $config): bool
    {
        return $this->isMatchingInsertTag($config);
    }

    public function getDcaTable(PickerConfig|null $config = null): string
    {
        return 'tl_article';
    }

    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        if ($this->supportsValue($config)) {
            $attributes['value'] = $this->getInsertTagValue($config);

            if ($flags = $this->getInsertTagFlags($config)) {
                $attributes['flags'] = $flags;
            }
        }

        return $attributes;
    }

    public function convertDcaValue(PickerConfig $config, mixed $value): int|string
    {
        return \sprintf($this->getInsertTag($config), $value);
    }

    protected function getRouteParameters(PickerConfig|null $config = null): array
    {
        return ['do' => 'article'];
    }

    protected function getDefaultInsertTag(): string
    {
        return '{{article_url::%s}}';
    }
}
