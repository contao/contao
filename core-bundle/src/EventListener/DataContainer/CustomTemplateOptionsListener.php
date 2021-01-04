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

use Contao\ContentElement;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Module;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomTemplateOptionsListener
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
    private $prefix;

    /**
     * @var string
     */
    private $proxyClass;

    public function __construct(ContaoFramework $framework, RequestStack $requestStack, array $customTemplates, string $prefix, string $proxyClass)
    {
        $controller = $framework->getAdapter(Controller::class);

        $this->controller = $controller;
        $this->requestStack = $requestStack;
        $this->prefix = $prefix;
        $this->customTemplates = $customTemplates;
        $this->proxyClass = $proxyClass;
    }

    public function __invoke(DataContainer $dc)
    {
        if ($this->isOverrideAll()) {
            return $this->getOverrideAllTemplates($this->prefix);
        }

        $defaultTemplate = $this->customTemplates[$dc->activeRecord->type] ?? null;

        // Extract default template from legacy class
        if (null === $defaultTemplate) {
            $class = $this->getLegacyClass($dc);

            if (!empty($class) && $class !== $this->proxyClass) {
                $defaultTemplate = $this->getTemplateFromObject(new $class($dc->activeRecord));
            }
        }

        if (null === $defaultTemplate) {
            $defaultTemplate = $this->prefix.$dc->activeRecord->type;
        }

        return $this->getTemplateGroup($defaultTemplate);
    }

    private function getLegacyClass(DataContainer $dc): ?string
    {
        switch ($dc->table) {
            case 'tl_content': return ContentElement::findClass($dc->activeRecord->type);

            case 'tl_module': return Module::findClass($dc->activeRecord->type);
        }

        return null;
    }

    private function getTemplateGroup(string $template): array
    {
        return $this->controller->getTemplateGroup($template.'_', [], $template);
    }

    private function getOverrideAllTemplates(string $prefix): array
    {
        // Add a blank option that allows us to reset all custom templates to the default one
        return array_merge(['' => '-'], $this->controller->getTemplateGroup($prefix));
    }

    private function isOverrideAll(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->query->has('act')) {
            return false;
        }

        return 'overrideAll' === $request->query->get('act');
    }

    /**
     * Uses the reflection API to return the default template name from the given object.
     */
    private function getTemplateFromObject($object): ?string
    {
        $reflection = new \ReflectionClass($object);

        try {
            $property = $reflection->getProperty('strTemplate');
        } catch (\ReflectionException $e) {
            // Property does not exist
            return null;
        }

        $property->setAccessible(true);

        return $property->getValue($object) ?: null;
    }
}
