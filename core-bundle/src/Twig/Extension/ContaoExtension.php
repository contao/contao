<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Extension;

use Contao\CoreBundle\Twig\Inheritance\DynamicExtendsTokenParser;
use Contao\CoreBundle\Twig\Inheritance\DynamicIncludeTokenParser;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Interop\ContaoEscaper;
use Contao\CoreBundle\Twig\Interop\ContaoEscaperNodeVisitor;
use Contao\CoreBundle\Twig\Interop\PhpTemplateProxyNodeVisitor;
use Contao\CoreBundle\Twig\Runtime\FigureRendererRuntime;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\CoreBundle\Twig\Runtime\LegacyTemplateFunctionsRuntime;
use Contao\CoreBundle\Twig\Runtime\PictureConfigurationRuntime;
use Contao\CoreBundle\Twig\Runtime\SchemaOrgRuntime;
use Contao\FrontendTemplate;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\Extension\EscaperExtension;
use Twig\TwigFunction;
use Webmozart\PathUtil\Path;

/**
 * @experimental
 */
final class ContaoExtension extends AbstractExtension
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var TemplateHierarchyInterface
     */
    private $hierarchy;

    /**
     * @var array
     */
    private $contaoEscaperFilterRules = [];

    public function __construct(Environment $environment, TemplateHierarchyInterface $hierarchy)
    {
        $this->environment = $environment;

        /** @var EscaperExtension $escaperExtension */
        $escaperExtension = $environment->getExtension(EscaperExtension::class);
        $escaperExtension->setEscaper('contao_html', [(new ContaoEscaper()), '__invoke']);

        $this->hierarchy = $hierarchy;

        // Use our escaper on all templates in the `@Contao` and `@Contao_*` namespaces
        $this->addContaoEscaperRule('%^@Contao(_[a-zA-Z0-9_-]*)?/%');
    }

    /**
     * Adds a Contao escaper rule.
     *
     * If a template name matches any of the defined rules, it will be processed
     * with the 'contao_html' escaper strategy. Make sure your rule will only
     * match templates with input encoded contexts!
     */
    public function addContaoEscaperRule(string $regularExpression): void
    {
        if (\in_array($regularExpression, $this->contaoEscaperFilterRules, true)) {
            return;
        }

        $this->contaoEscaperFilterRules[] = $regularExpression;
    }

    public function getNodeVisitors(): array
    {
        return [
            // Enables the 'contao_twig' escaper for Contao templates with
            // input encoding
            new ContaoEscaperNodeVisitor(
                function () {
                    return $this->contaoEscaperFilterRules;
                }
            ),
            // Allows rendering PHP templates with the legacy framework by
            // installing proxy nodes
            new PhpTemplateProxyNodeVisitor(self::class),
        ];
    }

    public function getTokenParsers(): array
    {
        return [
            // Overwrite the parsers for the 'extends' and 'include' tags to
            // additionally support the Contao template hierarchy
            new DynamicExtendsTokenParser($this->hierarchy),
            new DynamicIncludeTokenParser($this->hierarchy),
        ];
    }

    public function getFunctions(): array
    {
        $includeFunctionCallable = $this->getTwigIncludeFunction()->getCallable();

        return [
            // Overwrite the 'include' function to additionally support the
            // Contao template hierarchy
            new TwigFunction(
                'include',
                function (Environment $env, $context, $template, $variables = [], $withContext = true, $ignoreMissing = false, $sandboxed = false /* we need named arguments here */) use ($includeFunctionCallable) {
                    $args = \func_get_args();
                    $args[2] = DynamicIncludeTokenParser::adjustTemplateName((string) $template, $this->hierarchy);

                    return $includeFunctionCallable(...$args);
                },
                ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']]
            ),
            new TwigFunction(
                'contao_figure',
                [FigureRendererRuntime::class, 'render'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'picture_config',
                [PictureConfigurationRuntime::class, 'fromArray']
            ),
            new TwigFunction(
                'insert_tag',
                [InsertTagRuntime::class, 'replace'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'add_schema_org',
                [SchemaOrgRuntime::class, 'add']
            ),
            new TwigFunction(
                'contao_sections',
                [LegacyTemplateFunctionsRuntime::class, 'renderLayoutSections'],
                ['needs_context' => true, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'contao_section',
                [LegacyTemplateFunctionsRuntime::class, 'renderLayoutSection'],
                ['needs_context' => true, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'render_contao_backend_template',
                [LegacyTemplateFunctionsRuntime::class, 'renderContaoBackendTemplate'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @see \Contao\CoreBundle\Twig\Interop\PhpTemplateProxyNode
     * @see \Contao\CoreBundle\Twig\Interop\PhpTemplateProxyNodeVisitor
     *
     * @internal
     */
    public function renderLegacyTemplate(string $name, array $blocks, array $context): string
    {
        $template = Path::getFilenameWithoutExtension($name);

        $partialTemplate = new class($template) extends FrontendTemplate {
            public function setBlocks(array $blocks): void
            {
                $this->arrBlocks = $blocks;
                $this->arrBlockNames = array_keys($blocks);
            }

            public function parse(): string
            {
                return $this->inherit();
            }

            protected function renderTwigSurrogateIfExists(): ?string
            {
                return null;
            }
        };

        $partialTemplate->setData($context);
        $partialTemplate->setBlocks($blocks);

        return $partialTemplate->parse();
    }

    private function getTwigIncludeFunction(): TwigFunction
    {
        foreach ($this->environment->getExtension(CoreExtension::class)->getFunctions() as $function) {
            if ('include' === $function->getName()) {
                return $function;
            }
        }

        throw new \RuntimeException(sprintf('The %s class was expected to register the "include" Twig function but did not.', CoreExtension::class));
    }
}
