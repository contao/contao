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
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class ArticlePickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface
{
    private Security $security;

    /**
     * @internal Do not inherit from this class; decorate the "contao.picker.article_provider" service instead
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, ?TranslatorInterface $translator, Security $security)
    {
        parent::__construct($menuFactory, $router, $translator);

        $this->security = $security;
    }

    public function getName(): string
    {
        return 'articlePicker';
    }

    public function supportsContext($context): bool
    {
        return 'link' === $context && $this->security->isGranted('contao_user.modules', 'article');
    }

    public function supportsValue(PickerConfig $config): bool
    {
        return $this->isMatchingInsertTag($config);
    }

    public function getDcaTable(): string
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

    public function convertDcaValue(PickerConfig $config, $value): string
    {
        return sprintf($this->getInsertTag($config), $value);
    }

    protected function getRouteParameters(PickerConfig $config = null): array
    {
        return ['do' => 'article'];
    }

    protected function getDefaultInsertTag(): string
    {
        return '{{article_url::%s}}';
    }
}
