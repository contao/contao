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
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Symfony\Contracts\Service\ResetInterface;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

/**
 * @Callback(table="tl_content", target="fields.customTpl.options")
 * @Callback(table="tl_module", target="fields.customTpl.options")
 */
class CustomTemplateOptionsCallback implements ServiceAnnotationInterface, ResetInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var array<string,string>
     */
    private $fragmentTemplates = [];

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function __invoke(DataContainer $dc): array
    {
        switch ($dc->table) {
            case 'tl_content':
                $prefix = 'ce_';
                break;

            case 'tl_module':
                $prefix = 'mod_';
                break;

            default:
                throw new \RuntimeException('Unsupported table "'.$dc->table.'".');
        }

        $type = $dc->activeRecord->type;
        $template = $prefix.$type;

        if (isset($this->fragmentTemplates[$dc->table.'.'.$type])) {
            $template = $this->fragmentTemplates[$dc->table.'.'.$type];
        }

        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        return $controllerAdapter->getTemplateGroup($template.'_', [], $template);
    }

    /**
     * Registers a custom default template for a content element or front end module.
     *
     * @param $tag The service tag of the fragment.
     * @param $type The element or module type of the fragment.
     * @param $template The custom template definition.
     */
    public function addCustomFragmentTemplate(string $tag, string $type, string $template): void
    {
        switch ($tag) {
            case ContentElementReference::TAG_NAME:
                $table = 'tl_content';
                break;

            case FrontendModuleReference::TAG_NAME:
                $table = 'tl_module';
                break;

            default:
                throw new \InvalidArgumentException('Unsupported tag "'.$tag.'".');
        }

        $this->fragmentTemplates[$table.'.'.$type] = $template;
    }

    public function reset(): void
    {
        $this->fragmentTemplates = [];
    }
}
