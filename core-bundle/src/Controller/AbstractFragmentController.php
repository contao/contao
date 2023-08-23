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

use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
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
use Symfony\Component\HttpFoundation\Request;
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
        if (!$request = $this->container->get('request_stack')->getCurrentRequest()) {
            return null;
        }

        $pageModel = $request->attributes->get('pageModel');

        if ($pageModel instanceof PageModel) {
            return $pageModel;
        }

        return null;
    }

    /**
     * Creates a FragmentTemplate container object by template name or from the
     * "customTpl" field of the model and registers the effective template as
     * default view when using render().
     *
     * Calling getResponse() on the returned object will internally call
     * render() with the set parameters and return the response.
     *
     * Note: The $fallbackTemplateName argument will be removed in Contao 6;
     * always set a template via the fragment options, instead.
     */
    protected function createTemplate(Model $model, string|null $fallbackTemplateName = null): FragmentTemplate
    {
        $templateName = $this->getTemplateName($model, $fallbackTemplateName);
        $isLegacyTemplate = $this->isLegacyTemplate($templateName);
        $templateNameToView = static fn (string $name): string => "@Contao/$name.html.twig";

        // Allow calling render() without a view
        if (!$isLegacyTemplate) {
            $this->view = $templateNameToView($templateName);
        }

        $onGetResponse = function (FragmentTemplate $template, Response|null $preBuiltResponse) use ($isLegacyTemplate, $templateName, $templateNameToView): Response {
            if ($isLegacyTemplate) {
                // Render using the legacy framework
                $legacyTemplate = $this->container->get('contao.framework')->createInstance(FrontendTemplate::class, [$templateName]);
                $legacyTemplate->setData($template->getData());

                try {
                    $response = $legacyTemplate->getResponse();
                } catch (\Exception $e) {
                    // Enhance the exception if a modern template name is defined
                    // but still delegate to the legacy framework
                    if (null !== ($definedTemplateName = $this->options['template'] ?? null) && preg_match('/^Could not find template "\S+"$/', $e->getMessage())) {
                        throw new \LogicException(sprintf('Could neither find template "%s" nor the legacy fallback template "%s". Did you forget to create a default template or manually define the "template" property of the controller\'s service tag/attribute?', $definedTemplateName, $templateName), 0, $e);
                    }

                    throw $e;
                }

                if ($preBuiltResponse) {
                    return $preBuiltResponse->setContent($response->getContent());
                }

                $this->markResponseForInternalCaching($response);

                return $response;
            }

            // Directly render with Twig
            $context = $this->container->get('contao.twig.interop.context_factory')->fromData($template->getData());

            return $this->render($templateNameToView($template->getName()), $context, $preBuiltResponse);
        };

        $template = new FragmentTemplate($templateName, $onGetResponse);

        if ($isLegacyTemplate) {
            $template->setData($model->row());
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

    /**
     * @internal The addHeadlineToTemplate() method is considered internal in
     *           Contao 5 and won't be accessible anymore in Contao 6. Headline
     *           data is always added to the context of modern fragment
     *           templates.
     */
    protected function addHeadlineToTemplate(Template $template, array|string|null $headline): void
    {
        $this->triggerDeprecationIfCallingFromCustomClass(__METHOD__);

        $data = StringUtil::deserialize($headline);
        $template->headline = \is_array($data) ? $data['value'] ?? '' : $data;
        $template->hl = \is_array($data) && isset($data['unit']) ? $data['unit'] : 'h1';
    }

    /**
     * @internal The addCssAttributesToTemplate() method is considered internal
     *           in Contao 5 and won't be accessible anymore in Contao 6.
     *           Attributes data is always added to the context of modern
     *           fragment templates.
     */
    protected function addCssAttributesToTemplate(Template $template, string $templateName, array|string|null $cssID, array|null $classes = null): void
    {
        $this->triggerDeprecationIfCallingFromCustomClass(__METHOD__);

        $data = StringUtil::deserialize($cssID, true);
        $template->class = trim($templateName.' '.($data[1] ?? ''));
        $template->cssID = !empty($data[0]) ? ' id="'.$data[0].'"' : '';

        if ($classes) {
            $template->class .= ' '.implode(' ', $classes);
        }
    }

    /**
     * @internal The addPropertiesToTemplate() method is considered internal in
     *           Contao 5 and won't be accessible anymore in Contao 6. Custom
     *           properties are always added to the context of modern fragment
     *           templates.
     */
    protected function addPropertiesToTemplate(Template $template, array $properties): void
    {
        $this->triggerDeprecationIfCallingFromCustomClass(__METHOD__);

        foreach ($properties as $k => $v) {
            $template->{$k} = $v;
        }
    }

    /**
     * @internal The addSectionToTemplate() method is considered internal in
     *           Contao 5 and won't be accessible anymore in Contao 6. Section
     *           data is always added to the context of modern fragment
     *           templates.
     */
    protected function addSectionToTemplate(Template $template, string $section): void
    {
        $this->triggerDeprecationIfCallingFromCustomClass(__METHOD__);

        $template->inColumn = $section;
    }

    /**
     * Returns the type from the class name.
     *
     * @internal The getType() method is considered internal in Contao 5 and
     *           won't be accessible anymore in Contao 6. Retrieve the type
     *           from the fragment options instead.
     */
    protected function getType(): string
    {
        if (isset($this->options['type'])) {
            return $this->options['type'];
        }

        $className = strrchr(static::class, '\\');

        if (false === $className) {
            return static::class;
        }

        $className = ltrim($className, '\\');

        if (str_ends_with($className, 'Controller')) {
            $className = substr($className, 0, -10);
        }

        return Container::underscore($className);
    }

    /**
     * Renders a template. If $view is set to null, the default template of
     * this fragment will be rendered.
     *
     * By default, the returned response will have the appropriate headers set,
     * that allow our SubrequestCacheSubscriber to merge it with others of the
     * same page. Pass a prebuilt Response if you want to have full control -
     * no headers will be set then.
     */
    protected function render(string|null $view = null, array $parameters = [], Response|null $response = null): Response
    {
        $view ??= $this->view ?? throw new \InvalidArgumentException('Cannot derive template name, please make sure createTemplate() was called before or specify the template explicitly.');

        if (!$response) {
            $response = new Response();

            $this->markResponseForInternalCaching($response);
        }

        return parent::render($view, $parameters, $response);
    }

    protected function isBackendScope(Request|null $request = null): bool
    {
        $request ??= $this->container->get('request_stack')->getCurrentRequest();

        return null !== $request && $this->container->get('contao.routing.scope_matcher')->isBackendRequest($request);
    }

    /**
     * Marks the response to affect the caching of the current page but removes any default cache header.
     */
    protected function markResponseForInternalCaching(Response $response): void
    {
        $response->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $response->headers->remove('Cache-Control');
    }

    private function triggerDeprecationIfCallingFromCustomClass(string $method): void
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['class'];

        if (!\in_array($caller, [AbstractContentElementController::class, AbstractFrontendModuleController::class], true)) {
            trigger_deprecation('contao/core-bundle', '5.0', 'The "%s" method is considered internal and won\'t be accessible anymore in Contao 6.', $method);
        }
    }

    private function getTemplateName(Model $model, string|null $fallbackTemplateName): string
    {
        $exists = fn (string $template): bool => $this->container
            ->get('contao.twig.filesystem_loader')
            ->exists("@Contao/$template.html.twig")
        ;

        $shouldUseVariantTemplate = fn (string $variantTemplate): bool => $this->isLegacyTemplate($variantTemplate)
            ? !$this->isBackendScope()
            : $exists($variantTemplate);

        // Prefer using a custom variant template if defined and applicable
        if ($model->customTpl && $shouldUseVariantTemplate($model->customTpl)) {
            return $model->customTpl;
        }

        $definedTemplateName = $this->options['template'] ?? null;

        // Always use the defined name for legacy templates and for modern
        // templates that exist (= those that do not need to have a fallback)
        if (null !== $definedTemplateName && ($this->isLegacyTemplate($definedTemplateName) || $exists($definedTemplateName))) {
            return $definedTemplateName;
        }

        return $fallbackTemplateName ?? throw new \InvalidArgumentException('No template was set in the fragment options.');
    }
}
