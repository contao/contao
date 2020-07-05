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
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

class CustomTemplateOptionsCallback implements ServiceAnnotationInterface, ResetInterface
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
     * @var array<string,array<string,string>>
     */
    private $fragmentTemplates = [];

    public function __construct(ContaoFramework $framework, RequestStack $requestStack)
    {
        $this->controller = $framework->getAdapter(Controller::class);
        $this->requestStack = $requestStack;
    }

    /**
     * @Callback(table="tl_article", target="fields.customTpl.options")
     */
    public function onArticle(DataContainer $dc): array
    {
        return $this->getTemplateGroup($dc, 'mod_article');
    }

    /**
     * @Callback(table="tl_form", target="fields.customTpl.options")
     */
    public function onForm(DataContainer $dc): array
    {
        return $this->getTemplateGroup($dc, 'form_wrapper');
    }

    /**
     * @Callback(table="tl_form_field", target="fields.customTpl.options")
     */
    public function onFormField(DataContainer $dc): array
    {
        // Return all form_ templates in overrideAll mode
        if ($this->isOverrideAll()) {
            return $this->controller->getTemplateGroup('form_');
        }

        // Backwards compatibility
        if ('text' === $dc->activeRecord->type) {
            return $this->getTemplateGroup($dc, 'form_textfield');
        }

        return $this->getTemplateGroup($dc, 'form_'.$dc->activeRecord->type);
    }

    /**
     * @Callback(table="tl_content", target="fields.customTpl.options")
     */
    public function onContent(DataContainer $dc): array
    {
        return $this->getTemplateGroup($dc, 'ce_'.$dc->activeRecord->type);
    }

    /**
     * @Callback(table="tl_module", target="fields.customTpl.options")
     */
    public function onModule(DataContainer $dc): array
    {
        return $this->getTemplateGroup($dc, 'mod_'.$dc->activeRecord->type);
    }

    /**
     * Registers a custom default template for fragments.
     *
     * @param string $table    The data container table
     * @param string $type     The type of the Fragment
     * @param string $template The name of the custom default template
     */
    public function setFragmentTemplate(string $table, string $type, string $template): void
    {
        $this->fragmentTemplates[$table][$type] = $template;
    }

    public function reset(): void
    {
        $this->fragmentTemplates = [];
    }

    private function getTemplateGroup(DataContainer $dc, string $template): array
    {
        if (isset($dc->activeRecord->type, $this->fragmentTemplates[$dc->table][$dc->activeRecord->type])) {
            $template = $this->fragmentTemplates[$dc->table][$dc->activeRecord->type];
        }

        return $this->controller->getTemplateGroup($template.'_', [], $template);
    }

    /**
     * Checks whether act=overrideAll query parameter is set.
     */
    private function isOverrideAll(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return false;
        }

        if (!$request->query->has('act')) {
            return false;
        }

        return 'overrideAll' === $request->query->get('act');
    }
}
