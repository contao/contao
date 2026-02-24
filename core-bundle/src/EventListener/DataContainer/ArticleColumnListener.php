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

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Twig\Inspector\InspectionException;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\DataContainer;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback(table: 'tl_article', target: 'fields.inColumn.options')]
class ArticleColumnListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly PageRegistry $pageRegistry,
        private readonly Inspector $inspector,
        private readonly RequestStack $requestStack,
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(DataContainer $dc): array
    {
        $currentRecord = $dc->getCurrentRecord();

        if ($currentRecord) {
            return $this->getPageOptions((int) $currentRecord['pid']);
        }

        // Show all sections (e.g. "override all" mode)
        $selectedIds = $this->requestStack->getSession()->all()['CURRENT']['IDS'] ?? [];

        if ([] === $selectedIds) {
            return [];
        }

        $pageIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT pid FROM tl_article WHERE id IN (?)',
            [$selectedIds],
            [ArrayParameterType::INTEGER],
        );

        return array_intersect(...array_map(
            fn ($id) => $this->getPageOptions((int) $id),
            $pageIds,
        ));
    }

    private function getPageOptions(int $pageId): array
    {
        $pageModel = $this->framework->getAdapter(PageModel::class)->findWithDetails($pageId);

        if (!$pageModel) {
            return [];
        }

        if ($template = $this->pageRegistry->getPageTemplate($pageModel)) {
            return $this->getSlots($template);
        }

        $layout = $this->framework->getAdapter(LayoutModel::class)->findById($pageModel->layout);

        if (!$layout) {
            return [];
        }

        return match ($layout->type) {
            'default' => $this->getLayoutSections($layout),
            'modern' => $this->getSlots($layout->template),
            default => [],
        };
    }

    private function getSlots(string $template): array
    {
        try {
            $slots = $this->inspector
                ->inspectTemplate("@Contao/$template.html.twig")
                ->getSlots()
            ;
        } catch (InspectionException) {
            $slots = [];
        }

        $options = [];

        foreach ($slots as $slot) {
            $options[$slot] = "{% slot $slot %}";
        }

        return $options;
    }

    private function getLayoutSections(LayoutModel $layoutModel): array
    {
        $options = [];
        $modules = StringUtil::deserialize($layoutModel->modules);

        if (empty($modules) || !\is_array($modules)) {
            return [];
        }

        // Find all sections with an article module (see #6094)
        foreach ($modules as $module) {
            if (0 === (int) $module['mod'] && ($module['enable'] ?? null)) {
                $options[] = $module['col'];
            }
        }

        return Backend::convertLayoutSectionIdsToAssociativeArray($options);
    }
}
