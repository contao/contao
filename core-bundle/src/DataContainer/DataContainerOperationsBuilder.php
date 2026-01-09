<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * @internal
 */
class DataContainerOperationsBuilder extends AbstractDataContainerOperationsBuilder
{
    private string $table;

    private int|string|null $id = null;

    public function __construct(
        ContaoFramework $framework,
        private readonly Environment $twig,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct($framework);
    }

    public function __toString(): string
    {
        $operations = $this->cleanOperations();

        if (!$operations) {
            return '';
        }

        return $this->twig->render('@Contao/backend/data_container/operations.html.twig', [
            'id' => $this->id,
            'operations' => $operations,
            'has_primary' => [] !== array_filter(array_column($operations, 'primary'), static fn ($v) => null !== $v),
            'globalOperations' => false,
        ]);
    }

    public function initialize(string $table, int|string|null $id = null): self
    {
        $this->ensureNotInitialized();

        $builder = clone $this;
        $builder->id = $id;
        $builder->table = $table;
        $builder->operations = [];

        return $builder;
    }

    public function initializeWithButtons(string $table, array $record, DataContainer $dataContainer, callable|null $legacyCallback = null): self
    {
        $builder = $this->initialize($table, $record['id'] ?? null);

        if (!\is_array($GLOBALS['TL_DCA'][$table]['list']['operations'] ?? null)) {
            return $builder;
        }

        foreach ($GLOBALS['TL_DCA'][$table]['list']['operations'] as $k => $v) {
            if ('new' === $k) {
                continue;
            }

            if ('-' === $v) {
                $builder->addSeparator();
                continue;
            }

            $v = \is_array($v) ? $v : [$v];
            $operation = $builder->generateOperation($k, $v, $record, $dataContainer, $legacyCallback);

            if ($operation) {
                $builder->append($operation);
            }
        }

        return $builder;
    }

    public function initializeWithHeaderButtons(string $table, array $record, DataContainer $dataContainer, callable|null $legacyCallback = null): self
    {
        $builder = $this->initialize($table, $record['id'] ?? null);

        if (!\is_array($GLOBALS['TL_DCA'][$table]['list']['operations'] ?? null)) {
            return $builder;
        }

        $inputAdapter = $this->framework->getAdapter(Input::class);

        foreach ($GLOBALS['TL_DCA'][$table]['list']['operations'] as $k => $v) {
            if ('new' === $k) {
                continue;
            }

            // Show edit operation in the header by default (backwards compatibility)
            if ('edit' === $k && !isset($v['showInHeader'])) {
                $v['showInHeader'] = true;
            }

            if (empty($v['showInHeader']) || ('select' === $inputAdapter->get('act') && !($v['showOnSelect'] ?? null))) {
                continue;
            }

            $v = \is_array($v) ? $v : [$v];

            // Add the parent table to the href
            if (isset($v['href'])) {
                $v['href'] .= '&amp;table='.$table;
            } else {
                $v['href'] = 'table='.$table;
            }

            $operation = $builder->generateOperation($k, $v, $record, $dataContainer, $legacyCallback);

            if ($operation) {
                $builder->append($operation);
            }
        }

        return $builder;
    }

    /**
     * @param "pasteinto"|"pasteafter"|"pastetop"|"pasteroot" $type
     */
    public function addPasteButton(string $type, string $table, string|null $href): self
    {
        $icon = match ($type) {
            'pastetop' => 'pasteafter',
            'pasteroot' => 'pasteinto',
            default => $type,
        };

        [$label, $title] = $this->getLabelAndTitle($table, $type, $this->id);

        if (null === $href) {
            $this->append([
                'label' => $label,
                'icon' => $icon.'--disabled.svg',
                'primary' => true,
            ]);

            return $this;
        }

        $this->append([
            'label' => $label,
            'title' => $title,
            'attributes' => new HtmlAttributes('data-action="contao--scroll-offset#store"'),
            'icon' => $icon.'.svg',
            'href' => $href,
            'method' => 'POST',
            'primary' => !str_starts_with($type, 'pastenew'),
        ]);

        return $this;
    }

    /**
     * @param self::CREATE_* $mode
     */
    public function addNewButton(string $mode, string $table, int $pid, int|null $id = null): self
    {
        [$label, $title] = $this->getLabelAndTitle($table, 'pastenew'.$mode, $pid);

        $this->append([
            'label' => $label,
            'title' => $title,
            'attributes' => (new HtmlAttributes($GLOBALS['TL_DCA'][$table]['list']['operations']['new']['attributes'] ?? null))->set('data-action', 'contao--scroll-offset#store'),
            'icon' => $GLOBALS['TL_DCA'][$table]['list']['operations']['new']['icon'] ?? 'new.svg',
            'href' => $this->getNewHref($mode, $pid, $id),
            'method' => $GLOBALS['TL_DCA'][$table]['list']['operations']['new']['method'] ?? 'POST',
            'primary' => $GLOBALS['TL_DCA'][$table]['list']['operations']['new']['primary'] ?? false,
        ]);

        return $this;
    }

    private function generateOperation(string $name, array $operation, array $record, DataContainer $dataContainer, callable|null $legacyCallback = null): array|null
    {
        $config = new DataContainerOperation($name, $operation, $record, $dataContainer);

        $this->executeButtonCallback($operation['button_callback'] ?? null, $config, $legacyCallback);

        if (null !== ($html = $config->getHtml())) {
            if ('' === $html) {
                return null;
            }

            return [
                'html' => $html,
                'primary' => $config['primary'] ?? null,
            ];
        }

        $href = $this->generateHref($config, $record);

        if (false !== ($toggle = $this->handleToggle($config, $record, $operation, $href))) {
            return $toggle;
        }

        if ('show' === $name) {
            $config['attributes']->set(
                'onclick',
                \sprintf(
                    "Backend.openModalIframe({title:'%s', url:'%s'});return false",
                    $config['title'],
                    $href.(str_contains($href, '?') ? '&' : '?').'popup=1',
                ),
            );
        }

        if (null !== ($config['prefetch'] ?? null)) {
            $config['attributes']->set('data-turbo-prefetch', $config['prefetch'] ? 'true' : 'false');
        }

        if ($config['class'] ?? null) {
            $config['attributes']->addClass($config['class']);
        }

        // Add the key as CSS class
        $config['attributes']->addClass($name);

        return [
            'label' => $config['label'],
            'title' => $config['title'],
            'attributes' => $config['attributes'],
            'listAttributes' => $config['listAttributes'],
            'icon' => $config['icon'],
            'iconAttributes' => $config['iconAttributes'],
            'href' => $href,
            'method' => strtoupper($config['method'] ?? 'GET'),
            'primary' => $config['primary'] ?? null,
        ];
    }

    private function generateHref(DataContainerOperation $config, array $record): string|null
    {
        if (null !== $config->getUrl()) {
            return $config->getUrl();
        }

        if (!empty($config['route'])) {
            $params = ['id' => $record['id']];

            return $this->urlGenerator->generate($config['route'], $params);
        }

        if (isset($config['href'])) {
            return $this->framework->getAdapter(Backend::class)->addToUrl($config['href'].'&amp;id='.$record['id'].(Input::get('nb') ? '&amp;nc=1' : ''), addRequestToken: !($config['prefetch'] ?? false) && null === ($config['method'] ?? null));
        }

        return null;
    }

    /**
     * Returns true if this was a toggle operation (which is added to $operations).
     */
    private function handleToggle(DataContainerOperation $config, array $record, array $operation, string|null $href): array|false|null
    {
        parse_str(StringUtil::decodeEntities($config['href'] ?? $operation['href'] ?? ''), $params);

        if ('toggle' !== ($params['act'] ?? null) || !isset($params['field'])) {
            return false;
        }

        // Do not generate the toggle icon if the user does not have access to the field
        if (
            (
                true !== ($GLOBALS['TL_DCA'][$this->table]['fields'][$params['field']]['toggle'] ?? false)
                && true !== ($GLOBALS['TL_DCA'][$this->table]['fields'][$params['field']]['reverseToggle'] ?? false)
            ) || (
                DataContainer::isFieldExcluded($this->table, $params['field'])
                && !$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $this->table.'::'.$params['field'])
            )
        ) {
            return null;
        }

        $icon = $config['icon'];
        $_icon = pathinfo($config['icon'], PATHINFO_FILENAME).'_.'.pathinfo($config['icon'], PATHINFO_EXTENSION);

        if (str_contains($config['icon'], '/')) {
            $_icon = \dirname($config['icon']).'/'.$_icon;
        }

        if ('visible.svg' === $icon) {
            $_icon = 'invisible.svg';
        } elseif ('featured.svg' === $icon) {
            $_icon = 'unfeatured.svg';
        }

        $state = $record[$params['field']] ? 1 : 0;

        if (($config['reverse'] ?? false) || ($GLOBALS['TL_DCA'][$this->table]['fields'][$params['field']]['reverseToggle'] ?? false)) {
            $state = $record[$params['field']] ? 0 : 1;
        }

        if (isset($config['labelEnabled'])) {
            $labelEnabled = $config['labelEnabled'];
        } else {
            $labelEnabled = \is_array($operation['label'] ?? null) && isset($operation['label'][3]) ? $operation['label'][3] : $config['label'];
        }

        if (isset($config['labelDisabled'])) {
            $labelDisabled = $config['labelDisabled'];
        } else {
            $labelDisabled = \is_array($operation['label'] ?? null) && isset($operation['label'][4]) ? $operation['label'][4] : $config['label'];
        }

        if (isset($config['titleDisabled'])) {
            $titleDisabled = $config['titleDisabled'];
        } else {
            $titleDisabled = \is_array($operation['label'] ?? null) && isset($operation['label'][2]) ? \sprintf($operation['label'][2], $record['id']) : $config['title'];
        }

        $attributes = $config['attributes']
            ->set('data-action', 'contao--scroll-offset#store')
            ->set('onclick', 'return AjaxRequest.toggleField(this,'.('visible.svg' === $icon ? 'true' : 'false').')')
        ;

        $iconAttributes = (new HtmlAttributes())
            ->set('data-icon', $icon)
            ->set('data-icon-disabled', $_icon)
            ->set('data-state', $state)
            ->set('data-alt', $config['title'])
            ->set('data-alt-disabled', $titleDisabled)
        ;

        return [
            'href' => $href,
            'title' => $state ? $config['title'] : $titleDisabled,
            'label' => $state ? $labelEnabled : $labelDisabled,
            'attributes' => $attributes,
            'icon' => $state ? $icon : $_icon,
            'iconAttributes' => $iconAttributes,
            'primary' => $config['primary'] ?? null,
        ];
    }
}
