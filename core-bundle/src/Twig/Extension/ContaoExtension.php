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

use Contao\CoreBundle\Twig\Inheritance\DynamicEmbedTokenParser;
use Contao\CoreBundle\Twig\Inheritance\DynamicExtendsTokenParser;
use Contao\CoreBundle\Twig\Inheritance\DynamicIncludeTokenParser;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Interop\ContaoEscaper;
use Contao\CoreBundle\Twig\Interop\ContaoEscaperNodeVisitor;
use Contao\CoreBundle\Twig\Interop\PhpTemplateProxyNodeVisitor;
use Contao\FrontendTemplate;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\EscaperExtension;
use Webmozart\PathUtil\Path;

class ContaoExtension extends AbstractExtension
{
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
        /** @var EscaperExtension $escaperExtension */
        $escaperExtension = $environment->getExtension(EscaperExtension::class);

        $escaperExtension->setEscaper(
            'contao_html',
            [(new ContaoEscaper()), '__invoke']
        );

        $this->hierarchy = $hierarchy;

        // Use our escaper on all templates in the `@Contao` and `@Contao_*`
        // namespaces
        $this->addContaoEscaperRule('%^@Contao(_[a-zA-Z0-9_-]*)?/%');
    }

    /**
     * Add a contao escaper rule.
     *
     * If a template name matches any of the defined rules it will be processed
     * with the `contao_html` escaper strategy. Make sure your rule will only
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
            // Register parsers for the 'extends', 'include' and 'embed' tags
            // which will overwrite the ones of Twig's CoreExtension and
            // additionally support the Contao template hierarchy.
            new DynamicExtendsTokenParser($this->hierarchy),
            new DynamicIncludeTokenParser($this->hierarchy),
            new DynamicEmbedTokenParser($this->hierarchy),
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
            protected $blnEnableTwigSurrogateRendering = false;

            public function setBlocks(array $blocks): void
            {
                $this->arrBlocks = $blocks;
                $this->arrBlockNames = array_keys($blocks);
            }

            public function parse(): string
            {
                return $this->inherit();
            }
        };

        $partialTemplate->setData($context);
        $partialTemplate->setBlocks($blocks);

        return $partialTemplate->parse();
    }
}
