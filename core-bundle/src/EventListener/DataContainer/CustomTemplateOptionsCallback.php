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

/**
 * @Callback(table="tl_article", target="fields.customTpl.options")
 * @Callback(table="tl_content", target="fields.customTpl.options")
 * @Callback(table="tl_form", target="fields.customTpl.options")
 * @Callback(table="tl_form_field", target="fields.customTpl.options")
 * @Callback(table="tl_module", target="fields.customTpl.options")
 */
class CustomTemplateOptionsCallback implements ServiceAnnotationInterface, ResetInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

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
        $this->framework = $framework;
        $this->requestStack = $requestStack;
    }

    public function __invoke(DataContainer $dc): array
    {
        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        switch ($dc->table) {
            case 'tl_article':
                $template = 'mod_article';
                break;

            case 'tl_form':
                $template = 'form_wrapper';
                break;

            case 'tl_form_field':
                // Return all form_ templates in overrideAll mode
                if ($this->isOverrideAll()) {
                    return $controllerAdapter->getTemplateGroup('form_');
                }

                // Backwards compatibility
                if ('text' === $dc->activeRecord->type) {
                    $template = 'form_textfield';
                } else {
                    $template = 'form_'.$dc->activeRecord->type;
                }

                break;

            case 'tl_content':
            case 'tl_module':
                $type = $dc->activeRecord->type;
                $template = ('tl_content' === $dc->table ? 'ce_' : 'mod_').$type;

                if (isset($this->fragmentTemplates[$dc->table][$type])) {
                    $template = $this->fragmentTemplates[$dc->table][$type];
                }

                break;

            default:
                throw new \RuntimeException('Unsupported table "'.$dc->table.'".');
        }

        return $controllerAdapter->getTemplateGroup($template.'_', [], $template);
    }

    /**
     * Registers a custom default template for fragments.
     *
     * @param $type The fragment type.
     * @param $template The custom template definition.
     */
    public function setFragmentTemplate(string $table, string $type, string $template): void
    {
        $this->fragmentTemplates[$table][$type] = $template;
    }

    public function reset(): void
    {
        $this->fragmentTemplates = [];
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
