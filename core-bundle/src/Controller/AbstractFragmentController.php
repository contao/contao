<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Fragment\FragmentOptionsAwareInterface;
use Contao\FrontendTemplate;
use Contao\Model;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Container;

abstract class AbstractFragmentController extends Controller implements FragmentOptionsAwareInterface
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param array $options
     */
    public function setFragmentOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * Creates a template by name or from customTpl in model.
     *
     * @param Model  $model
     * @param string $templateName
     *
     * @return Template
     */
    protected function createTemplate(Model $model, string $templateName): Template
    {
        if ($model->customTpl) {
            $templateName = $model->customTpl;
        }

        $template = $this->get('contao.framework')->createInstance(FrontendTemplate::class, [$templateName]);
        $template->setData($model->row());

        return $template;
    }

    /**
     * Adds the headline to the template.
     *
     * @param Template     $template
     * @param string|array $headline
     */
    protected function addHeadlineToTemplate(Template $template, $headline): void
    {
        $data = StringUtil::deserialize($headline);
        $template->headline = \is_array($data) ? $data['value'] : $data;
        $template->hl = \is_array($data) ? $data['unit'] : 'h1';
    }

    /**
     * Adds the CSS ID and class to the template.
     *
     * @param Template     $template
     * @param string       $templateName
     * @param string|array $cssID
     * @param array|null   $classes
     */
    protected function addCssAttributesToTemplate(Template $template, string $templateName, $cssID, array $classes = null): void
    {
        $data = StringUtil::deserialize($cssID, true);
        $template->class = trim($templateName.' '.($data[1] ?? ''));
        $template->cssID = !empty($data[0]) ? ' id="'.$data[0].'"' : '';

        if (\is_array($classes)) {
            $template->class .= ' '.implode(' ', $classes);
        }
    }

    /**
     * Adds the article section to the template.
     *
     * @param Template $template
     * @param string   $section
     */
    protected function addSectionToTemplate(Template $template, string $section): void
    {
        $template->inColumn = $section;
    }

    /**
     * Tags the current response with given cache tags.
     *
     * @param array $tags
     */
    protected function tagResponse(array $tags): void
    {
        if (!$this->has('fos_http_cache.http.symfony_response_tagger')) {
            return;
        }

        $this->get('fos_http_cache.http.symfony_response_tagger')->addTags($tags);
    }

    /**
     * Returns the type from the class name.
     *
     * @return string
     */
    protected function getType(): string
    {
        if (isset($this->options['type'])) {
            return $this->options['type'];
        }

        $className = ltrim(strrchr(static::class, '\\'), '\\');

        if ('Controller' === substr($className, -10)) {
            $className = substr($className, 0, -10);
        }

        return Container::underscore($className);
    }
}
