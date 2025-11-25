<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Inspector\InspectionException;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\DataContainer;
use Contao\Input;

class ThemeLayoutListener
{
    public function __construct(
        private readonly FinderFactory $finderFactory,
        private readonly Inspector $inspector,
        private readonly ContaoFramework $framework,
    ) {
    }

    #[AsCallback(table: 'tl_layout', target: 'fields.template.options')]
    public function getTemplateOptions(DataContainer $dc): array
    {
        if ($this->isLegacy($dc)) {
            return $this->framework
                ->getAdapter(Controller::class)
                ->getTemplateGroup('fe_')
            ;
        }

        return $this->finderFactory
            ->create()
            ->identifier('page/layout')
            ->extension('html.twig')
            ->withVariants()
            ->excludePartials()
            ->asTemplateOptions(false)
        ;
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
}
