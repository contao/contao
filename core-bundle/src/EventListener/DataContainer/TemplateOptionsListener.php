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
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback(table: 'tl_content', target: 'fields.customTpl.options')]
#[AsCallback(table: 'tl_module', target: 'fields.customTpl.options')]
class TemplateOptionsListener
{
    private array $customTemplates = [];

    /**
     * @var Adapter<Controller>
     */
    private Adapter $controller;

    public function __construct(
        ContaoFramework $framework,
        private RequestStack $requestStack,
        private string $templatePrefix,
        private string|null $proxyClass = null,
    ) {
        $controller = $framework->getAdapter(Controller::class);

        $this->controller = $controller;
    }

    public function __invoke(DataContainer $dc): array
    {
        if ($this->isOverrideAll()) {
            // Add a blank option that allows us to reset all custom templates to the default one
            return array_merge(['' => '-'], $this->controller->getTemplateGroup($this->templatePrefix));
        }

        $type = $dc->getCurrentRecord()['type'] ?? null;
        $defaultTemplate = $this->customTemplates[$type] ?? $this->getLegacyDefaultTemplate($dc);

        if (empty($defaultTemplate)) {
            $defaultTemplate = $this->templatePrefix.$type;
        }

        return $this->controller->getTemplateGroup($defaultTemplate.'_', [], $defaultTemplate);
    }

    public function setCustomTemplates(array $customTemplates): void
    {
        $this->customTemplates = $customTemplates;
    }

    /**
     * Uses the reflection API to return the default template from a legacy class.
     */
    private function getLegacyDefaultTemplate(DataContainer $dc): string|null
    {
        if (null === $this->proxyClass || !method_exists($this->proxyClass, 'findClass')) {
            return null;
        }

        $class = $this->proxyClass::findClass($dc->getCurrentRecord()['type'] ?? null);

        if (empty($class) || $class === $this->proxyClass) {
            return null;
        }

        $properties = (new \ReflectionClass($class))->getDefaultProperties();

        return $properties['strTemplate'] ?? null;
    }

    private function isOverrideAll(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->query->has('act')) {
            return false;
        }

        return 'overrideAll' === $request->query->get('act');
    }
}
