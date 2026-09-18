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
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Date;
use Contao\DC_Table;
use Contao\Image;
use Contao\MemberGroupModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentElementViewListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly DcaUrlAnalyzer $dcaUrlAnalyzer,
        private readonly TranslatorInterface $translator,
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

    private function generateContentTypeLabel(array $row): string
    {
        $label = $this->trans("CTE.$row[type].0", $row['type']);
        $title = null;

        match ($row['type']) {
            'alias' => $this->updateElement($row, $label, $title),
            'module' => $this->updateModule($row, $label, $title),
            'article' => $this->updateArticle($row, $label, $title),
            'headline' => $this->updateHeadline($row, $label),
            default => null,
        };

        if ($row['title'] ?? null) {
            $title = $row['title'];
        }

        if ($title) {
            $label = $title.' <span class="tl_gray">['.$label.']</span>';
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

    private function updateElement(array $row, string &$label, string|null &$title): void
    {
        $href = $this->dcaUrlAnalyzer->getEditUrl('tl_content', $row['cteAlias']);

        $label .= \sprintf(
            ' <a href="%s" onclick="Backend.openModalIframe({ title: \'%s ID %s\', url:this.href + \'&amp;popup=1&amp;nb=1\' });return false">ID %s</a>',
            $href,
            StringUtil::specialchars($label),
            $row['cteAlias'],
            $row['cteAlias'],
        );

        [$type, $title] = ($this->connection->fetchNumeric('SELECT type, title FROM tl_content WHERE id=?', [$row['cteAlias']]) ?: []) + ['', null];

        if ($type) {
            $label .= ' ('.$this->trans('CTE.'.$type.'.0', $type).')';
        }
    }

    private function updateModule(array $row, string &$label, string|null &$title): void
    {
        $href = $this->urlGenerator->generate('contao_backend', [
            'do' => 'themes',
            'table' => 'tl_module',
            'act' => 'edit',
            'id' => $row['module'],
        ]);

        $label .= \sprintf(
            ' <a href="%s" onclick="Backend.openModalIframe({ title: \'%s ID %s\', url:this.href + \'&amp;popup=1&amp;nb=1\' });return false">ID %s</a>',
            $href,
            StringUtil::specialchars($label),
            $row['module'],
            $row['module'],
        );

        [$type, $title] = ($this->connection->fetchNumeric('SELECT type, name FROM tl_module WHERE id=?', [$row['module']]) ?: []) + ['', null];

        if ($type) {
            $label .= ' ('.$this->trans('FMD.'.$type.'.0', $type, 'contao_modules').')';
        }
    }

    private function updateArticle(array $row, string &$label, string|null &$title): void
    {
        $href = $this->urlGenerator->generate('contao_backend', [
            'do' => 'article',
            'act' => 'edit',
            'id' => $row['articleAlias'],
        ]);

        $label .= \sprintf(
            ' <a href="%s"  onclick="Backend.openModalIframe({ title: \'%s ID %s\', url:this.href + \'&amp;popup=1&amp;nb=1\' });return false">ID %s</a>',
            $href,
            StringUtil::specialchars($label),
            $row['articleAlias'],
            $row['articleAlias'],
        );

        $title = (string) $this->connection->fetchOne('SELECT title FROM tl_article WHERE id=?', [$row['articleAlias']]) ?: null;
    }

    private function updateHeadline(array $row, string &$label): void
    {
        // Add the headline level (see #5858)
        if (\is_array($headline = StringUtil::deserialize($row['headline']))) {
            $label .= ' ('.$headline['unit'].')';
        }
    }

    private function trans(string $transId, string $default, string $domain = 'contao_default'): string
    {
        $label = $this->translator->trans($transId, [], $domain);

        if ($transId === $label) {
            return $default;
        }

        return $label;
    }
}
