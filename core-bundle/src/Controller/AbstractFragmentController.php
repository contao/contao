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

use Contao\CoreBundle\EventListener\SubrequestCacheSubscriber;
use Contao\CoreBundle\Fragment\FragmentOptionsAwareInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\FrontendTemplate;
use Contao\Model;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractFragmentController extends AbstractController implements FragmentOptionsAwareInterface
{
    protected array $options = [];
    private string|null $view = null;

    public function setFragmentOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @return array<string>
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['request_stack'] = RequestStack::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;
        $services['contao.twig.filesystem_loader'] = ContaoFilesystemLoader::class;
        $services['contao.twig.interop.context_factory'] = ContextFactory::class;

        return $services;
    }

    protected function getPageModel(): PageModel|null
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if (null !== $request && ($pageModel = $request->attributes->get('pageModel')) instanceof PageModel) {
            return $pageModel;
        }

        return null;
    }

    /**
     * Creates a FragmentTemplate container object by template name or from the
     * "customTpl" field of the model and registers the effective template as
     * default view when using render().
     *
     * Calling getResponse() method on the returned object will internally call
     * render() with the set parameters and return the response.
     *
     * Note: The $fallbackTemplateName argument will be removed in Contao 6;
     * always set a template via the fragment options, instead.
     */
    protected function createTemplate(Model $model, string|null $fallbackTemplateName = null): FragmentTemplate
    {
        $templateName = $this->getTemplateName($model, $fallbackTemplateName);

        if ($isLegacyTemplate = $this->isLegacyTemplate($templateName)) {
            // TODO: enable deprecation once existing fragments have been adjusted
            // trigger_deprecation('contao/core-bundle', '5.0', 'Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');
        }

        // Allow calling render() without a view
        $this->view = !$isLegacyTemplate ? "@Contao/$templateName.html.twig" : null;

        $onGetResponse = function (FragmentTemplate $template, Response|null $preBuiltResponse) use ($templateName, $isLegacyTemplate): Response {
            if ($isLegacyTemplate) {
                // Render using the legacy framework
                $legacyTemplate = $this->container->get('contao.framework')->createInstance(FrontendTemplate::class, [$templateName]);
                $legacyTemplate->setData($template->getData());

                $response = $legacyTemplate->getResponse();

                if (null !== $preBuiltResponse) {
                    return $preBuiltResponse->setContent($response->getContent());
                }

                $this->markResponseForInternalCaching($response);

                return $response;
            }

            // Directly render with Twig
            $context = $this->container->get('contao.twig.interop.context_factory')->fromData($template->getData());

            return $this->render($template->getName(), $context, $preBuiltResponse);
        };

        $template = new FragmentTemplate($templateName, $onGetResponse);

        if ($isLegacyTemplate) {
            $template->setData((array) $model->row());
        }

        return $template;
    }

    /**
     * @internal
     */
    protected function isLegacyTemplate(string $templateName): bool
    {
        return !str_contains($templateName, '/');
    }

    protected function addHeadlineToTemplate(Template $template, array|string|null $headline): void
    {
        $data = StringUtil::deserialize($headline);
        $template->headline = \is_array($data) ? $data['value'] : $data;
        $template->hl = \is_array($data) ? $data['unit'] : 'h1';
    }

    protected function addCssAttributesToTemplate(Template $template, string $templateName, array|string|null $cssID, array $classes = null): void
    {
        $data = StringUtil::deserialize($cssID, true);
        $template->class = trim($templateName.' '.($data[1] ?? ''));
        $template->cssID = !empty($data[0]) ? ' id="'.$data[0].'"' : '';

        if (!empty($classes)) {
            $template->class .= ' '.implode(' ', $classes);
        }
    }

    protected function addPropertiesToTemplate(Template $template, array $properties): void
    {
        foreach ($properties as $k => $v) {
            $template->{$k} = $v;
        }
    }

    protected function addSectionToTemplate(Template $template, string $section): void
    {
        $template->inColumn = $section;
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

        if (str_ends_with($className, 'Controller')) {
            $className = substr($className, 0, -10);
        }

        return Container::underscore($className);
    }

    /**
     * Renders a template. If $view is set to null, the default template of
     * this fragment will rendered.
     *
     * By default, the returned response will have the appropriate headers set,
     * that allow our SubrequestCacheSubscriber to merge it with others of the
     * same page. Pass a prebuilt Response if you want to have full control -
     * no headers will be set then.
     */
    protected function render(string|null $view = null, array $parameters = [], Response $response = null): Response
    {
        $view ??= $this->view ?? throw new \InvalidArgumentException('Cannot derive template name, please make sure createTemplate() was called before or specify the template explicitly.');

        if (null === $response) {
            $response = new Response();

            $this->markResponseForInternalCaching($response);
        }

        return parent::render($view, $parameters, $response);
    }

    /**
     * Marks the response to affect the caching of the current page but removes any default cache header.
     */
    protected function markResponseForInternalCaching(Response $response): void
    {
        $response->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $response->headers->remove('Cache-Control');
    }

    private function getTemplateName(Model $model, string|null $fallbackTemplateName): string
    {
        // If set, use the custom template unless it is a back end request
        if ($model->customTpl && !$this->isBackendScope()) {
            return $model->customTpl;
        }

        $definedTemplateName = $this->options['template'] ?? null;

        // Always use the defined name for legacy templates and for modern
        // templates that exist (= those that do not need to have a fallback)
        if (null !== $definedTemplateName && ($this->isLegacyTemplate($definedTemplateName) || $this->container->get('contao.twig.filesystem_loader')->exists("@Contao/$definedTemplateName.html.twig"))) {
            return $definedTemplateName;
        }

        return $fallbackTemplateName ?? throw new \InvalidArgumentException('No template was set in the fragment options.');
    }
}
