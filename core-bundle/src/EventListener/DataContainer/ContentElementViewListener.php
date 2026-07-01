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
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Date;
use Contao\DC_Table;
use Contao\Image;
use Contao\MemberGroupModel;
use Contao\StringUtil;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ContentElementViewListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
        private readonly Environment $twig,
    ) {
    }

    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    public function adjustListView(DC_Table $dc): void
    {
        if ('tl_theme' !== $dc->parentTable) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_content']['list']['sorting'] = [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['title'],
            'panelLayout' => 'search,filter,sort,limit',
            'defaultSearchField' => 'title',
            'headerFields' => ['name', 'author', 'tstamp'],
        ];
    }

    #[AsCallback('tl_content', 'list.label.label')]
    public function generateLabel(array $row, string $label, DC_Table $dc): array|string
    {
        if ('tl_theme' !== $dc->parentTable) {
            return $this->generateGridLabel($row);
        }

        return $this->generateContentTypeLabel($row);
    }

    #[AsCallback('tl_content', 'list.label.group')]
    public function generateGroupLabel(string $group, int|string $mode, string $field, array $row, DC_Table $dc): string
    {
        if ('tl_theme' !== $dc->parentTable) {
            return $group;
        }

        return 'type' === $field ? $row['type'] : $group;
    }

    private function generateGridLabel(array $row): array
    {
        $type = $this->generateContentTypeLabel($row);

        $objModel = $this->framework->createInstance(ContentModel::class);
        $objModel->setRow($row);

        try {
            $preview = StringUtil::insertTagToSrc($this->framework->getAdapter(Controller::class)->getContentElement($objModel));
        } catch (\Throwable $exception) {
            $preview = $this->twig->createTemplate('<p class="tl_error">{{ message }}</p>')->render(['message' => $exception->getMessage()]);
        }

        if (!empty($row['sectionHeadline'])) {
            $sectionHeadline = StringUtil::deserialize($row['sectionHeadline'], true);

            if (!empty($sectionHeadline['value']) && !empty($sectionHeadline['unit'])) {
                $preview = $this->twig->createTemplate('<{{unit}}>{{ value }}</{{unit}}>')->render($sectionHeadline).$preview;
            }
        }

        // Strip HTML comments to check if the preview is empty
        if ('' === trim(preg_replace('/<!--(.|\s)*?-->/', '', $preview))) {
            $preview = '';
        }

        return [$type, $preview, $row['invisible'] ?? null ? 'unpublished' : 'published'];
    }

    private function generateContentTypeLabel(array $row): string
    {
        $transId = "CTE.$row[type].0";
        $label = $this->translator->trans($transId, [], 'contao_default');

        if ($transId === $label) {
            $label = $row['type'];
        }

        // Add the ID of the aliased element
        if ('alias' === $row['type']) {
            $label .= ' ID '.(int) ($row['cteAlias'] ?? 0);
        }

        // Add the headline level (see #5858)
        if ('headline' === $row['type'] && \is_array($headline = StringUtil::deserialize($row['headline']))) {
            $label .= ' ('.$headline['unit'].')';
        }

        // Show the title
        if ($row['title'] ?? null) {
            $label = $this->twig->createTemplate('{{ title }} <span class="tl_gray">[{{ label }}]</span>')->render(['title' => $row['title'], 'label' => $label]);
        }

        // Add the protection status
        if ($row['protected'] ?? null) {
            $groupIds = StringUtil::deserialize($row['groups'], true);
            $groupNames = [];

            if (!empty($groupIds)) {
                $groupIds = array_map(intval(...), $groupIds);

                if (false !== ($pos = array_search(-1, $groupIds, true))) {
                    $groupNames[] = $this->translator->trans('MSC.guests', [], 'contao_default');
                    unset($groupIds[$pos]);
                }

                if ([] !== $groupIds && null !== ($groups = $this->framework->getAdapter(MemberGroupModel::class)->findMultipleByIds($groupIds))) {
                    $groupNames += $groups->fetchEach('name');
                }
            }

            $label = $this->framework->getAdapter(Image::class)->getHtml('protected.svg').' '.$label;
            $label .= $this->twig->createTemplate(" <span class=\"tl_gray\">({{ 'MSC.protected'|trans({}, 'contao_default') }}{{ group_names ? ': ' ~ group_names|join(', ') : '' }})</span>")->render(['group_names' => $groupNames]);
        }

        if (($row['start'] ?? null) && ($row['stop'] ?? null)) {
            $label .= $this->twig->createTemplate(" <span class=\"tl_gray\">({{ 'MSC.showFromTo'|trans([from, to], 'contao_default') }})</span>")->render(['from' => Date::parse(Config::get('datimFormat'), $row['start']), 'to' => Date::parse(Config::get('datimFormat'), $row['stop'])]);
        } elseif ($row['start'] ?? null) {
            $label .= $this->twig->createTemplate(" <span class=\"tl_gray\">({{ 'MSC.showFrom'|trans([from], 'contao_default') }})</span>")->render(['from' => Date::parse(Config::get('datimFormat'), $row['start'])]);
        } elseif ($row['stop'] ?? null) {
            $label .= $this->twig->createTemplate(" <span class=\"tl_gray\">({{ 'MSC.showTo'|trans([to], 'contao_default') }})</span>")->render(['to' => Date::parse(Config::get('datimFormat'), $row['stop'])]);
        }

        return $label;
    }
}
