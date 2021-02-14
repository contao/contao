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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;

class TemplateOptionsListener
{
    /**
     * @var Controller
     */
    private $controller;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var array
     */
    private $customTemplates;

    /**
     * @var string
     */
    private $templatePrefix;

    /**
     * @var string
     */
    private $proxyClass;

    public function __construct(ContaoFramework $framework, RequestStack $requestStack, array $customTemplates, string $templatePrefix, string $proxyClass = null)
    {
        /** @var Controller $controller */
        $controller = $framework->getAdapter(Controller::class);

        $this->controller = $controller;
        $this->requestStack = $requestStack;
        $this->customTemplates = $customTemplates;
        $this->templatePrefix = $templatePrefix;
        $this->proxyClass = $proxyClass;
    }

    public function __invoke(DataContainer $dc): array
    {
        if ($this->isOverrideAll()) {
            // Add a blank option that allows us to reset all custom templates to the default one
            return array_merge(['' => '-'], $this->controller->getTemplateGroup($this->templatePrefix));
        }

        $defaultTemplate = $this->customTemplates[$dc->activeRecord->type] ?? $this->getLegacyDefaultTemplate($dc);

        if (empty($defaultTemplate)) {
            $defaultTemplate = $this->templatePrefix.$dc->activeRecord->type;
        }

        return $this->controller->getTemplateGroup($defaultTemplate.'_', [], $defaultTemplate);
    }

    /**
     * Uses the reflection API to return the default template from a legacy class.
     */
    private function getLegacyDefaultTemplate(DataContainer $dc): ?string
    {
        if (null === $this->proxyClass || !method_exists($this->proxyClass, 'findClass')) {
            return null;
        }

        $class = $this->proxyClass::findClass($dc->activeRecord->type);

        if (empty($class) || $class === $this->proxyClass) {
            return null;
        }

        $properties = (new \ReflectionClass($class))->getDefaultProperties();

        return \is_string($properties['strTemplate'] ?? null) ? $properties['strTemplate'] : null;
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
