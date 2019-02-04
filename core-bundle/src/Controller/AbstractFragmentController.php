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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendTemplate;
use Contao\Model;
use Contao\StringUtil;
use Contao\Template;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;

abstract class AbstractFragmentController extends AbstractController implements FragmentOptionsAwareInterface
{
    /**
     * @var array
     */
    protected $options = [];

    public function setFragmentOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                'contao.framework' => ContaoFramework::class,
                'fos_http_cache.http.symfony_response_tagger' => '?'.SymfonyResponseTagger::class,
            ]
        );
    }

    /**
     * Creates a template by name or from the "customTpl" field of the model.
     */
    protected function createTemplate(Model $model, string $templateName): Template
    {
        if (isset($this->options['template'])) {
            $templateName = $this->options['template'];
        }

        if ($model->customTpl) {
            $templateName = $model->customTpl;
        }

        $template = $this->get('contao.framework')->createInstance(FrontendTemplate::class, [$templateName]);
        $template->setData($model->row());

        return $template;
    }

    /**
     * @param string|array $headline
     */
    protected function addHeadlineToTemplate(Template $template, $headline): void
    {
        $data = StringUtil::deserialize($headline);
        $template->headline = \is_array($data) ? $data['value'] : $data;
        $template->hl = \is_array($data) ? $data['unit'] : 'h1';
    }

    /**
     * @param string|array $cssID
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

    protected function addSectionToTemplate(Template $template, string $section): void
    {
        $template->inColumn = $section;
    }

    protected function tagResponse(array $tags): void
    {
        if (!$this->has('fos_http_cache.http.symfony_response_tagger')) {
            return;
        }

        $this->get('fos_http_cache.http.symfony_response_tagger')->addTags($tags);
    }

    /**
     * Returns the type from the class name.
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
