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
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @internal
 */
class DataContainerGlobalOperationsBuilder extends AbstractDataContainerOperationsBuilder
{
    private string $table;

    public function __construct(
        ContaoFramework $framework,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
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
            'operations' => $operations,
            'has_primary' => [] !== array_filter(array_column($operations, 'primary'), static fn ($v) => null !== $v),
            'globalOperations' => true,
        ]);
    }

    public function initialize(string $table): self
    {
        $this->ensureNotInitialized();

        $builder = clone $this;
        $builder->table = $table;
        $builder->operations = [];

        return $builder;
    }

    public function addBackButton(string|null $href = null): self
    {
        $this->ensureInitialized();

        if (null === $href) {
            $href = $this->framework->getAdapter(System::class)->getReferer(true);
        } elseif (str_contains($href, '=') && !str_contains($href, '?')) {
            $href = $this->urlGenerator->generate('contao_backend').'?'.$href;
        }

        $this->append([
            'href' => $href,
            'label' => $this->translator->trans('MSC.backBT', [], 'contao_default'),
            'title' => $this->translator->trans('MSC.backBTTitle', [], 'contao_default'),
            'attributes' => (new HtmlAttributes())->addClass('header_back')->set('accesskey', 'b')->set('data-action', 'contao--scroll-offset#discard'),
            'primary' => true,
        ]);

        return $this;
    }

    public function addClearClipboardButton(): self
    {
        $this->ensureInitialized();

        $this->append([
            'href' => $this->framework->getAdapter(Backend::class)->addToUrl('clipboard=1', true, [], false),
            'label' => $this->translator->trans('MSC.clearClipboard', [], 'contao_default'),
            'attributes' => (new HtmlAttributes())->addClass('header_clipboard')->set('accesskey', 'x'),
            'method' => 'POST',
            'primary' => true,
        ]);

        return $this;
    }

    /**
     * @param self::CREATE_* $mode
     */
    public function addNewButton(string $mode, int|null $pid = null): self
    {
        $this->ensureInitialized();

        $url = match ($mode) {
            'create' => 'act=create',
            'paste' => 'act=paste&amp;mode=create',
            'paste_after' => 'act=create&amp;mode=1',
            'paste_into' => 'act=create&amp;mode=2',
        };

        if (null !== $pid) {
            $url .= '&amp;pid='.$pid;
        }

        [$label, $title] = $this->getLabelAndTitle($this->table, 'new');

        $href = $this->framework->getAdapter(Backend::class)->addToUrl($url, true, [], false);

        $this->append([
            'href' => $href,
            'label' => $label,
            'title' => $title,
            'attributes' => (new HtmlAttributes())->addClass('header_new')->set('accesskey', 'n')->set('data-action', 'contao--scroll-offset#store'),
            'method' => 'POST',
            'primary' => true,
        ]);

        return $this;
    }

    public function addGlobalButtons(DataContainer $dataContainer, callable|null $legacyCallback = null): self
    {
        $this->ensureInitialized();

        if (!\is_array($GLOBALS['TL_DCA'][$this->table]['list']['global_operations'] ?? null)) {
            return $this;
        }

        $inputAdapter = $this->framework->getAdapter(Input::class);

        foreach ($GLOBALS['TL_DCA'][$this->table]['list']['global_operations'] as $k => $v) {
            if ('-' === $v) {
                $this->addSeparator();
                continue;
            }

            if (!($v['showOnSelect'] ?? null) && 'select' === $inputAdapter->get('act')) {
                continue;
            }

            $v = \is_array($v) ? $v : [$v];
            $operation = $this->generateOperation($k, $v, $dataContainer, $legacyCallback);

            if ($operation) {
                $this->append($operation);
            }
        }

        return $this;
    }

    private function generateOperation(string $name, array $operation, DataContainer $dataContainer, callable|null $legacyCallback = null): array|null
    {
        $config = new DataContainerOperation($name, $operation, null, $dataContainer);

        $this->executeButtonCallback($operation['button_callback'] ?? null, $config, $legacyCallback);

        if (null !== ($html = $config->getHtml())) {
            if ('' === $html) {
                return null;
            }

            return [
                'html' => $html,
                'primary' => $config['primary'] ?? ('all' === $name ? true : null),
            ];
        }

        $href = $this->generateHref($config);

        if ($config['class'] ?? null) {
            $config['attributes']->addClass($config['class']);
        }

        return [
            'href' => $href,
            'label' => $config['label'],
            'title' => $config['title'],
            'attributes' => $config['attributes'],
            'icon' => $config['icon'] ?? null,
            'primary' => $config['primary'] ?? ('all' === $name ? true : null),
        ];
    }

    private function generateHref(DataContainerOperation $config): string|null
    {
        if (null !== $config->getUrl()) {
            return $config->getUrl();
        }

        if (!empty($config['route'])) {
            return $this->urlGenerator->generate($config['route']);
        }

        if (isset($config['href'])) {
            if (!str_contains($config['href'], '=') || str_contains($config['href'], '?')) {
                return $config['href'];
            }

            return $this->framework->getAdapter(Backend::class)->addToUrl($config['href'], true, [], !($config['prefetch'] ?? false));
        }

        return null;
    }
}
