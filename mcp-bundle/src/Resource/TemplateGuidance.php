<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Resource;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\String\HtmlAttributes;
use Mcp\Capability\Attribute\McpResource;

final class TemplateGuidance
{
    #[McpResource(
        uri: 'contao://template-guidance',
        name: 'contao_template_guidance',
        title: 'Contao template editing guidance',
        description: 'Version-specific rules for choosing, overriding, validating, saving and recovering Contao Twig templates with snapshots.',
        mimeType: 'text/markdown',
    )]
    public function templateGuidance(): string
    {
        return str_replace('__CONTAO_VERSION__', ContaoCoreBundle::getVersion(), <<<'MARKDOWN'
            # Contao template editing guidance

            Installed Contao version: __CONTAO_VERSION__

            All `contao_template_*` tools expose Template Studio functionality and require the current backend user to have administrator privileges (`ROLE_ADMIN`).

            ## Choosing an edit target

            - Discover and read templates instead of guessing identifiers or source.
            - Template identifiers come from `contao_template_discover` and do not include a file extension.
            - Use `contao_template_list_themes` when the task targets a theme. Pass the returned slug unchanged to every subsequent tool; omit `theme` consistently for global user templates.
            - Analyze impact before choosing the template to override.
            - Prefer the narrowest template matching the requested scope.
            - A `component/*` change can affect unrelated template families; a concrete template is narrower.
            - Use the reported resolution chain and transitive consumers to account for user, theme, application and bundle overrides.

            ## Extending templates and components

            - Prefer extending/importing a template and changing a block over copying its complete source.
            - Call `parent()` when enhancing a block; omit it only when intentionally replacing the block.
            - Treat `@Contao/...` as a managed name resolved through user, theme, application and bundle templates.
            - Respect shadowing and inspect transitive consumers before making broad changes.
            - Reusable components conventionally live below `component/`, document mandatory and optional variables with `@var`, and expose a top-level `*_component` block plus smaller blocks.
            - Import reusable blocks with `{% use '@Contao/component/_name.html.twig' %}` and invoke them with `block()`.
            - Pass component input through a scoped `{% with {…} %}` context instead of leaking temporary variables into the caller.
            - Some generic components accept either a grouped object or the current context, e.g. `list|default(_context)`; the grouped object takes precedence.
            - Preserve fine-grained blocks around wrappers, attributes and inner content. They are intentional customization points.

            ## Code style

            - Match the style and naming of the template being extended and nearby templates.
            - Indent nested Twig and HTML with four spaces, and separate logical sections with blank lines.
            - Use descriptive `snake_case` names for new blocks and template variables, including element-specific attribute variables.
            - Preserve existing block structure and Twig whitespace controls (`{%-` and `-%}`) when editing rendered output.

            ## Variables and attributes

            - Use `|default`, `|default(null)` or `is defined` for optional extension variables.
            - Preserve existing `HtmlAttributes` extension points. Build core defaults first and merge the identically named incoming variable last.
            - Attribute variable names describe their element or role, such as `figure_attributes`, `link_attributes` or `script_attributes`.
            - Merge data-owned attribute collections before the template extension variable. The extension variable remains the final customization layer.
            - See `contao://twig/html-attributes` before changing attributes.

            ## Output, scripts and page composition

            - Match the surrounding output policy: trusted rich HTML commonly uses `sanitize_html('contao')` before `insert_tag_html`; do not introduce `raw` for ordinary content.
            - Register scripts and styles through `{% add … to body|head|stylesheets %}` when the surrounding component uses response-context assets.
            - Add CSP nonces to inline scripts with `.setIfExists('nonce', csp_nonce('script-src'))`.
            - Keep initialization options in dedicated blocks when a component exposes them.
            - Preserve `{% defer %}` when output depends on response context populated later in rendering.
            - Preserve page slots and their `slot()` fallback behavior.

            ## Workflow

            - Read the template before changing it. The returned `operations` are the operations currently available for that identifier and theme context; do not assume another operation is supported.
            - Create a snapshot before the first mutation in a task and keep its hash until the work is accepted.
            - If `can_edit` is false and `create` is available, create the override before saving. Creation writes generated default content, which must then be replaced with the intended complete source.
            - Validation compiles the complete proposed source in context but does not render it with runtime data. Treat a successful result as a syntax and compilation check, not proof of correct output.
            - For an advertised `create_*` or `rename_*` operation, first call `contao_template_execute_operation` with empty `parameters`. This returns suggested values and the allowed identifier pattern without creating or renaming a file. Then call it again with a valid `identifier_fragment`.
            - Re-read and re-analyze the result after creating, renaming or changing an override. Use the new identifier returned by a rename operation.
            - Dynamic template references and references created in PHP may require human review.

            ## Snapshot recovery

            - A snapshot covers the entire project `templates/` directory, not just the template being edited. Its history is stored in the application cache, separate from the project Git repository, and can be cleared with that cache; it is not a durable backup.
            - Use `contao_template_diff` with the saved hash to inspect changes against that recovery point. Omitting the hash compares with the latest snapshot.
            - Use `contao_template_snapshots` to find snapshot hashes. An explicit hash can be used with both `contao_template_diff` and `contao_template_rollback`.
            - `contao_template_rollback` replaces the entire `templates/` directory. Only call it when the user explicitly requests a restore and identifies the intended snapshot, preferably by hash. If hash is omitted, the latest snapshot is restored.
            MARKDOWN);
    }

    #[McpResource(
        uri: 'contao://twig/html-attributes',
        name: 'contao_twig_html_attributes',
        title: 'Contao Twig HtmlAttributes guidance',
        description: 'Version-specific guidance for nondestructive HTML attribute changes with attrs().',
        mimeType: 'text/markdown',
    )]
    public function htmlAttributes(): string
    {
        $availableMethods = array_values(array_filter(
            ['mergeWith', 'set', 'setIfExists', 'unset', 'addClass', 'removeClass', 'addStyle', 'removeStyle'],
            static fn (string $method): bool => method_exists(HtmlAttributes::class, $method),
        ));

        return strtr(
            <<<'MARKDOWN'
                # Contao Twig HtmlAttributes guidance

                Installed Contao version: __CONTAO_VERSION__
                Available mutation methods: `__AVAILABLE_METHODS__`

                `attrs()` creates a mutable `HtmlAttributes` object whose string representation is safe inside an HTML tag.

                ## Extensible construction pattern

                Core templates normally create a fresh object, apply their defaults, and merge the identically named extension variable last:

                ```twig
                {% set accordion_header_attributes = attrs()
                    .addClass('handorgel__header')
                    .mergeWith(accordion_header_attributes|default)
                %}
                ```

                This works whether the variable was supplied by a caller, set by a child template, or is absent. The order matters: the final merge is the customization layer. Classes and styles are merged by `HtmlAttributes`; ordinary attributes supplied later can replace earlier values.

                When domain data also provides attributes, merge it before the template-level extension variable:

                ```twig
                {% set figure_attributes = attrs()
                    .mergeWith(figure.options.attr|default)
                    .mergeWith(figure_attributes|default)
                %}
                ```

                Use `attrs(existing_attributes|default)` when no core defaults are needed and the goal is simply to normalize an optional collection before further mutation.

                ## Mutation rules

                - Use `mergeWith()` to preserve and merge another attribute collection.
                - Use `addClass()` and `removeClass()` for classes; they normalize and deduplicate class names.
                - Use `addStyle()` and `removeStyle()` for individual CSS properties.
                - Use `set()` when replacement of one named attribute is intentional.
                - Passing `false` to `set()` removes the attribute.
                - Use `setIfExists()` when a falsy value should leave the attribute absent.
                - Use `unset()` for an explicit conditional removal.
                - Most mutation methods accept a final condition argument and become a no-op when it is falsy.
                - Use conditional mutations instead of duplicating complete HTML tags in `{% if %}` branches.
                - Boolean attributes can be represented by an empty value, e.g. `.set('data-open', condition)` or `.set('disabled', '')` as appropriate to the attribute contract.
                - Do not render attributes to a string and then manipulate that string.

                ```twig
                {% set image_attributes = attrs()
                    .setIfExists('loading', loading|default)
                    .addClass('is-enhanced')
                    .mergeWith(image_attributes|default)
                %}

                <img{{ image_attributes }}>
                ```
                MARKDOWN,
            [
                '__CONTAO_VERSION__' => ContaoCoreBundle::getVersion(),
                '__AVAILABLE_METHODS__' => implode('`, `', $availableMethods),
            ],
        );
    }
}
