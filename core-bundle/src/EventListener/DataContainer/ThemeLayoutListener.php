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

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Inspector\InspectionException;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\DataContainer;
use Contao\Input;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

class ThemeLayoutListener implements ResetInterface
{
    private array|null $selectedLayoutTypes = null;

    public function __construct(
        private readonly FinderFactory $finderFactory,
        private readonly Inspector $inspector,
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
        private readonly Connection $connection,
    ) {
    }

    #[AsCallback(table: 'tl_layout', target: 'fields.template.options')]
    public function getTemplateOptions(DataContainer $dc): array
    {
        $legacyOptions = $this->framework
            ->getAdapter(Controller::class)
            ->getTemplateGroup('fe_')
        ;

        if ($this->isLegacy($dc)) {
            return $legacyOptions;
        }

        $modernOptions = $this->finderFactory
            ->create()
            ->identifier('page/layout')
            ->extension('html.twig')
            ->withVariants()
            ->excludePartials()
            ->asTemplateOptions(false)
        ;

        if (!$this->isOverrideAll()) {
            return $modernOptions;
        }

        $selectedLayoutTypes = $this->getSelectedLayoutTypes();

        if ([] === $selectedLayoutTypes) {
            return $modernOptions;
        }

        $options = [];

        foreach ($selectedLayoutTypes as $type) {
            $options += match ($type) {
                'default' => $legacyOptions,
                default => $modernOptions,
            };
        }

        return $options;
    }

    #[AsCallback(table: 'tl_layout', target: 'fields.modules.load')]
    public function defineAvailableSlots(string $value, DataContainer $dc): string
    {
        if ($this->isLegacy($dc) || null === ($identifier = $this->getTemplateIdentifier($dc)) || !str_contains($identifier, '/')) {
            return $value;
        }

        try {
            $slots = $this->inspector
                ->inspectTemplate("@Contao/$identifier.html.twig")
                ->getSlots()
            ;
        } catch (InspectionException) {
            $slots = [];
        }

        $GLOBALS['TL_DCA']['tl_layout']['fields']['modules']['eval']['slots'] = $slots;

        return $value;
    }

    #[AsCallback(table: 'tl_layout', target: 'fields.template.attributes')]
    public function adjustFieldsForLegacyType(array $attributes, DataContainer $dc): array
    {
        if ($this->isLegacy($dc)) {
            $attributes['mandatory'] = false;
            $attributes['submitOnChange'] = false;
        } elseif ($this->isOverrideAll()) {
            if (\in_array('default', $this->getSelectedLayoutTypes(), true)) {
                $attributes['mandatory'] = false;
            }
        }

        return $attributes;
    }

    #[AsCallback(table: 'tl_layout', target: 'config.onbeforesubmit')]
    public function resetTemplateForType(array $values, DataContainer $dc): array
    {
        if (!isset($values['type'])) {
            return $values;
        }

        $current = $dc->getCurrentRecord();

        if ($current['type'] !== $values['type']) {
            $values['template'] = '';
        }

        return $values;
    }

    public function reset(): void
    {
        $this->selectedLayoutTypes = null;
    }

    private function isLegacy(DataContainer $dc): bool
    {
        $input = $this->framework->getAdapter(Input::class);

        if ('default' === $input->post('type')) {
            return true;
        }

        $currentRecord = $dc->getCurrentRecord();

        return null !== $currentRecord && 'default' === $currentRecord['type'];
    }

    private function getTemplateIdentifier(DataContainer $dc): string|null
    {
        $input = $this->framework->getAdapter(Input::class);

        return $input->post('template') ?? $dc->getCurrentRecord()['template'] ?? null;
    }

    private function isOverrideAll(): bool
    {
        return 'overrideAll' === $this->requestStack->getCurrentRequest()?->query->get('act');
    }

    private function getSelectedLayoutTypes(): array
    {
        if (null !== $this->selectedLayoutTypes) {
            return $this->selectedLayoutTypes;
        }

        $selectedIds = $this->requestStack->getSession()->all()['CURRENT']['IDS'] ?? [];

        if ([] === $selectedIds) {
            return $this->selectedLayoutTypes = [];
        }

        return $this->selectedLayoutTypes = $this->connection->fetchFirstColumn('SELECT DISTINCT type FROM tl_layout WHERE id IN (?)',
            [$selectedIds],
            [ArrayParameterType::INTEGER],
        );
    }
}
