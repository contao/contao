<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio;

use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Inspector\InspectionException;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Inspector\TemplateInformation;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @internal
 */
class Autocomplete
{
    /**
     * @var list<string>
     */
    private readonly array $components;

    /**
     * @var list<string>
     */
    private readonly array $filters;

    /**
     * @var list<string>
     */
    private readonly array $functions;

    /**
     * @var array<string, list<string>>
     */
    private readonly array $globals;

    public function __construct(
        private readonly Inspector $inspector,
        FinderFactory $finderFactory,
        private readonly Environment $twig,
    ) {
        $this->components = $finderFactory
            ->create()
            ->identifierRegex('%^component/%')
            ->asIdentifierList()
        ;

        $this->filters = array_map(
            static fn (TwigFilter $filter): string => $filter->getName(),
            array_filter(
                $this->twig->getFilters(),
                static fn (TwigFilter $filter): bool => !$filter->isDeprecated(),
            ),
        );

        $this->functions = array_map(
            static fn (TwigFunction $function): string => $function->getName(),
            array_filter(
                $this->twig->getFunctions(),
                static fn (TwigFunction $function): bool => !$function->isDeprecated(),
            ),
        );

        $this->globals = array_map(
            static fn (mixed $global): array => array_filter(
                array_map(
                    static function (string $method): string|null {
                        if (1 === preg_match('/^(?:get|set|is|[^_])(.+)/', $method, $matches)) {
                            return lcfirst($matches[1]);
                        }

                        return null;
                    },
                    \is_object($global) ? get_class_methods($global) : [],
                ),
            ),
            $this->twig->getGlobals(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getCompletions(string $logicalName): array
    {
        try {
            $templateInformation = $this->inspector->inspectTemplate($logicalName);
        } catch (InspectionException) {
            $templateInformation = null;
        }

        $completions = [
            $this->getCompletionForExtendsTag(
                $templateInformation,
                ContaoTwigUtil::getIdentifier($logicalName),
                ContaoTwigUtil::getExtension($logicalName),
            ),
            ...$this->getCompletionsForUseTags($templateInformation),
            ...$this->getCompletionsForBlocks($templateInformation),
            ...$this->getCompletionsForDefaultMarkup(),
        ];

        return array_values(array_filter($completions));
    }

    /**
     * @return array<string, mixed>
     */
    private function getCompletionForExtendsTag(TemplateInformation|null $templateInformation, string $identifier, string $extension): array
    {
        $currentExtends = $templateInformation?->getExtends();

        if ($currentExtends && ContaoTwigUtil::getIdentifier($currentExtends) === $identifier) {
            return [];
        }

        return [
            'caption' => "extends \"@Contao/$identifier.$extension\"",
            'snippet' => "{% extends \"@Contao/$identifier.$extension\" %}\n\n",
            'meta' => 'extends',
            'type' => 'value',
            'score' => 50,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getCompletionsForUseTags(TemplateInformation|null $templateInformation): array
    {
        $currentUses = array_map(
            static fn (array $use): string => ContaoTwigUtil::getIdentifier($use[0]),
            $templateInformation?->getUses() ?? [],
        );

        $completions = [];

        // "use" tags for components
        foreach (array_diff($this->components, $currentUses) as $identifier) {
            $completions[] = [
                'caption' => "use \"@Contao/$identifier.html.twig\"",
                'snippet' => "{% use \"@Contao/$identifier.html.twig\" %}\n",
                'meta' => 'use',
                'type' => 'value',
                'score' => 40,
            ];
        }

        // "block" function for components
        foreach ($currentUses as $identifier) {
            $componentName = substr($identifier, 11);

            $completions[] = [
                'caption' => "component \"$componentName\"",
                'value' => "{{ block('{$componentName}_component') }}\n",
                'meta' => 'function',
                'type' => 'value',
                'score' => 30,
            ];

            $completions[] = [
                'caption' => "component \"$componentName\" with …",
                'snippet' => "{% with {{$componentName}: \$1} %}\n    {{ block('{$componentName}_component') }}\n{% endwith %}\n",
                'meta' => 'function',
                'type' => 'snippet',
                'score' => 31,
            ];
        }

        return $completions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getCompletionsForBlocks(TemplateInformation|null $templateInformation): array
    {
        return array_map(
            static fn (string $blockName): array => [
                'caption' => "block \"$blockName\"",
                'snippet' => "{% block $blockName %}\n    {{ parent() }}\n    $1\n{% endblock %}",
                'meta' => 'block',
                'type' => 'snippet',
                'score' => 20,
            ],
            $templateInformation?->getBlockNames() ?? [],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getCompletionsForDefaultMarkup(): array
    {
        $completions = [];

        foreach ($this->filters as $filter) {
            $completions[] = [
                'caption' => "…|$filter",
                'value' => $filter,
                'meta' => 'filter',
                'type' => 'value',
                'score' => 11,
            ];
        }

        foreach ($this->functions as $function) {
            $completions[] = [
                'caption' => "$function(…)",
                'snippet' => "$function($1)",
                'meta' => 'function',
                'type' => 'snippet',
                'score' => 10,
            ];
        }

        foreach ($this->globals as $global => $methods) {
            foreach ($methods as $method) {
                $completions[] = [
                    'caption' => "$global.$method",
                    'value' => "$global.$method",
                    'meta' => 'global function',
                    'type' => 'value',
                    'score' => 9,
                ];
            }

            if (empty($methods)) {
                $completions[] = [
                    'value' => $global,
                    'meta' => 'global value',
                    'type' => 'value',
                    'score' => 9,
                ];
            }
        }

        return $completions;
    }
}
