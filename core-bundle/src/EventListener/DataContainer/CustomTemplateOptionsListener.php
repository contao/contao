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
use Contao\ContentProxy;
use Contao\Controller;
use Contao\CoreBundle\Fragment\FragmentOptionsAwareInterface;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Module;
use Contao\ModuleProxy;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomTemplateOptionsListener implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var Controller
     */
    private $controller;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var FragmentRegistry
     */
    private $fragmentRegistry;

    public function __construct(ContaoFramework $framework, RequestStack $requestStack, FragmentRegistry $fragmentRegistry)
    {
        /** @var Controller $controller */
        $controller = $framework->getAdapter(Controller::class);

        $this->controller = $controller;
        $this->requestStack = $requestStack;
        $this->fragmentRegistry = $fragmentRegistry;
    }

    /**
     * @Callback(table="tl_article", target="fields.customTpl.options")
     */
    public function onArticle(DataContainer $dc): array
    {
        return $this->getTemplateGroup('mod_article');
    }

    /**
     * @Callback(table="tl_content", target="fields.customTpl.options")
     */
    public function onContent(DataContainer $dc): array
    {
        if ($this->isOverrideAll()) {
            return $this->getOverrideAllTemplates('ce_');
        }

        $class = ContentElement::findClass($dc->activeRecord->type);

        if (ContentProxy::class === $class || empty($class)) {
            $defaultTemplate = $this->getFragmentTemplate(ContentElementReference::TAG_NAME.'.'.$dc->activeRecord->type);
        } else {
            $defaultTemplate = $this->getTemplateFromObject(new $class($dc->activeRecord));
        }

        if (null === $defaultTemplate) {
            $defaultTemplate = 'ce_'.$dc->activeRecord->type;
        }

        return $this->getTemplateGroup($defaultTemplate);
    }

    /**
     * @Callback(table="tl_form", target="fields.customTpl.options")
     */
    public function onForm(DataContainer $dc): array
    {
        return $this->getTemplateGroup('form_wrapper');
    }

    /**
     * @Callback(table="tl_form_field", target="fields.customTpl.options")
     */
    public function onFormField(DataContainer $dc): array
    {
        if ($this->isOverrideAll()) {
            return $this->getOverrideAllTemplates('form_');
        }

        // Backwards compatibility
        if ('text' === $dc->activeRecord->type) {
            return $this->getTemplateGroup('form_textfield');
        }

        return $this->getTemplateGroup('form_'.$dc->activeRecord->type);
    }

    /**
     * @Callback(table="tl_module", target="fields.customTpl.options")
     */
    public function onModule(DataContainer $dc): array
    {
        if ($this->isOverrideAll()) {
            return $this->getOverrideAllTemplates('mod_');
        }

        $class = Module::findClass($dc->activeRecord->type);

        if (ModuleProxy::class === $class || empty($class)) {
            $defaultTemplate = $this->getFragmentTemplate(FrontendModuleReference::TAG_NAME.'.'.$dc->activeRecord->type);
        } else {
            $defaultTemplate = $this->getTemplateFromObject(new $class($dc->activeRecord));
        }

        if (null === $defaultTemplate) {
            $defaultTemplate = 'mod_'.$dc->activeRecord->type;
        }

        return $this->getTemplateGroup($defaultTemplate);
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
     * Returns the configured template for the given fragment.
     */
    private function getFragmentTemplate(string $identifier): ?string
    {
        if (!$this->fragmentRegistry->has($identifier)) {

            return null;
        }

        $config = $this->fragmentRegistry->get($identifier);

        if (!$this->container->has($config->getController())) {

            return null;
        }

        $controller = $this->container->get($config->getController());

        if (!$controller instanceof FragmentOptionsAwareInterface) {

            return null;
        }

        $options = $controller->getFragmentOptions();

        return $options['template'] ?? null;
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
