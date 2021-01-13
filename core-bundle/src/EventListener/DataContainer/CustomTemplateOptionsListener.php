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

    public function __construct(ContaoFramework $framework, RequestStack $requestStack, array $customTemplates, string $prefix, string $proxyClass = null)
    {
        /** @var Controller $controller */
        $controller = $framework->getAdapter(Controller::class);

        $this->controller = $controller;
        $this->requestStack = $requestStack;
        $this->customTemplates = $customTemplates;
        $this->prefix = $prefix;
        $this->proxyClass = $proxyClass;
    }

    public function __invoke(DataContainer $dc)
    {
        if ($this->isOverrideAll()) {
            // Add a blank option that allows us to reset all custom templates to the default one
            return array_merge(['' => '-'], $this->controller->getTemplateGroup($this->prefix));
        }

        $defaultTemplate = $this->customTemplates[$dc->activeRecord->type] ?? $this->getLegacyDefaultTemplate($dc);

        if (null === $defaultTemplate) {
            $defaultTemplate = $this->prefix.$dc->activeRecord->type;
        }

        return $this->getTemplateGroup($defaultTemplate);
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

        $object = new $class($dc->activeRecord);
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

    private function getTemplateGroup(string $template): array
    {
        return $this->controller->getTemplateGroup($template.'_', [], $template);
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
