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

class LayoutTemplateListener
{
    public function __construct(
        private readonly FinderFactory $finderFactory,
        private readonly Inspector $inspector,
        private readonly ContaoFramework $framework,
    ) {
    }

    #[AsCallback(table: 'tl_layout', target: 'fields.version.load')]
    public function onLoadVersion(string $value, DataContainer $dc): string
    {
        if ($this->isModern($dc)) {
            $templateField = &$GLOBALS['TL_DCA']['tl_layout']['fields']['template'];

            $templateField['eval']['mandatory'] = true;
            $templateField['eval']['submitOnChange'] = true;
        }

        return $value;
    }

    #[AsCallback(table: 'tl_layout', target: 'fields.template.options')]
    public function getTemplateOptions(DataContainer $dc): array
    {
        if (!$this->isModern($dc)) {
            return $this->framework->getAdapter(Controller::class)->getTemplateGroup('fe_');
        }

        return $this->finderFactory
            ->create()
            ->identifier('layout/*')
            ->enableWildcardSupport()
            ->extension('html.twig')
            ->withVariants()
            ->asTemplateOptions()
        ;
    }

    #[AsCallback(table: 'tl_layout', target: 'fields.modules.load')]
    public function setSlots(string $value, DataContainer $dc): string
    {
        if (!$this->isModern($dc) || null === ($identifier = $this->getTemplateIdentifier($dc)) || !str_contains($identifier, '/')) {
            return $value;
        }

        $slots = [];

        try {
            $slots = $this->inspector->inspectTemplate("@Contao/$identifier.html.twig")->getSlots();
        } catch (InspectionException) {
            // ignore
        }

        $GLOBALS['TL_DCA']['tl_layout']['fields']['modules']['eval']['slots'] = $slots;

        return $value;
    }

    private function isModern(DataContainer $dc): bool
    {
        $input = $this->framework->getAdapter(Input::class);

        if ('modern' === $input->post('version')) {
            return true;
        }

        $currentRecord = $dc->getCurrentRecord();

        return null !== $currentRecord && 'modern' === $currentRecord['version'];
    }

    private function getTemplateIdentifier(DataContainer $dc): string|null
    {
        $input = $this->framework->getAdapter(Input::class);

        return $input->post('template') ?? $dc->getCurrentRecord()['template'] ?? null;
    }
}
