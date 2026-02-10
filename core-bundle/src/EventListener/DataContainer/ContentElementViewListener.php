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

#[AsCallback(table: 'tl_content', target: 'config.onload')]
class ContentElementViewListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(DC_Table $dc): void
    {
        if ('tl_theme' !== $dc->parentTable) {
            $GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback'] = $this->generateGridLabel(...);

            return;
        }

        $GLOBALS['TL_DCA']['tl_content']['list']['sorting'] = [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'defaultSearchField' => 'title',
            'headerFields' => ['name', 'author', 'tstamp'],
        ];

        $GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback'] = $this->generateContentTypeLabel(...);
        $GLOBALS['TL_DCA']['tl_content']['list']['label']['group_callback'] = $this->generateGroupLabel(...);
    }

    /**
     * @internal
     */
    public function generateGridLabel(array $row): array
    {
        $type = $this->generateContentTypeLabel($row);

        $objModel = $this->framework->createInstance(ContentModel::class);
        $objModel->setRow($row);

        try {
            $preview = StringUtil::insertTagToSrc($this->framework->getAdapter(Controller::class)->getContentElement($objModel));
        } catch (\Throwable $exception) {
            $preview = '<p class="tl_error">'.StringUtil::specialchars($exception->getMessage()).'</p>';
        }

        if (!empty($row['sectionHeadline'])) {
            $sectionHeadline = StringUtil::deserialize($row['sectionHeadline'], true);

            if (!empty($sectionHeadline['value']) && !empty($sectionHeadline['unit'])) {
                $preview = '<'.$sectionHeadline['unit'].'>'.$sectionHeadline['value'].'</'.$sectionHeadline['unit'].'>'.$preview;
            }
        }

        // Strip HTML comments to check if the preview is empty
        if ('' === trim(preg_replace('/<!--(.|\s)*?-->/', '', $preview))) {
            $preview = '';
        }

        return [$type, $preview, $row['invisible'] ?? null ? 'unpublished' : 'published'];
    }

    private function generateGroupLabel(string $group, int|string $mode, string $field, array $row): string
    {
        return 'type' === $field ? $row['type'] : $group;
    }

    private function generateContentTypeLabel(array $row): string
    {
        $transId = 'CTE.'.$row['type'].'0';
        $label = $this->translator->trans($transId, [], 'contao_default');

        if ($transId === $label) {
            $label = $this->translator->trans("CTE.$row[type].0", [], 'contao_default');
        }

        // Add the ID of the aliased element
        if ('alias' === $row['type']) {
            $label .= ' ID '.($row['cteAlias'] ?? 0);
        }

        // Add the headline level (see #5858)
        if ('headline' === $row['type'] && \is_array($headline = StringUtil::deserialize($row['headline']))) {
            $label .= ' ('.$headline['unit'].')';
        }

        // Show the title
        if ($row['title'] ?? null) {
            $label = $row['title'].' <span class="tl_gray">['.$label.']</span>';
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
            $label .= ' <span class="tl_gray">('.$this->translator->trans('MSC.protected', [], 'contao_default').($groupNames ? ': '.implode(', ', $groupNames) : '').')</span>';
        }

        if (($row['start'] ?? null) && ($row['stop'] ?? null)) {
            $label .= ' <span class="tl_gray">('.$this->translator->trans('MSC.showFromTo', [Date::parse(Config::get('datimFormat'), $row['start']), Date::parse(Config::get('datimFormat'), $row['stop'])], 'contao_default').')</span>';
        } elseif ($row['start'] ?? null) {
            $label .= ' <span class="tl_gray">('.$this->translator->trans('MSC.showFrom', [Date::parse(Config::get('datimFormat'), $row['start'])], 'contao_default').')</span>';
        } elseif ($row['stop'] ?? null) {
            $label .= ' <span class="tl_gray">('.$this->translator->trans('MSC.showTo', [Date::parse(Config::get('datimFormat'), $row['stop'])], 'contao_default').')</span>';
        }

        return $label;
    }
}
