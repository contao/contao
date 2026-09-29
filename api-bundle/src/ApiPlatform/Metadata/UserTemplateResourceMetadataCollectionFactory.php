<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\ApiPlatform\State\UserTemplateStateProcessor;
use Contao\ApiBundle\ApiPlatform\State\UserTemplateStateProvider;
use Contao\ApiBundle\Dto\UserTemplate;
use Contao\ApiBundle\Dto\UserTemplateOperation;
use Contao\ApiBundle\Dto\UserTemplateUpdate;
use Contao\CoreBundle\Twig\Studio\Operation\OperationDescriptionInterface;

final class UserTemplateResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly iterable $templateStudioOperations,
        private readonly bool $templateStudioEnabled = true,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        if (UserTemplate::class !== $resourceClass || !$this->templateStudioEnabled) {
            return $this->decorated->create($resourceClass);
        }

        $operations = [
            new Get(
                '/user_templates',
                description: <<<'MARKDOWN'
                    Discover available templates. The response contains a "tree" with the template hierarchy.
                    Use a template identifier from this tree to read its source and available operations via
                    GET /user_templates/{name}. Names do not include the file extension.
                    Pass the optional "theme" query parameter to select a theme slug; omit it for global user
                    templates. Use the same theme context when reading or modifying a template. No request body
                    is required.
                    MARKDOWN,
                name: 'contao_api_user_template_discover',
                parameters: [
                    'query' => new QueryParameter(
                        key: 'query',
                        schema: ['type' => 'string'],
                        description: 'Optional case-insensitive substring used to filter template identifiers.',
                    ),
                ],
            ),
            new Get(
                '/user_template_themes',
                description: 'Discover valid theme slugs for use with the theme query parameter.',
                name: 'contao_api_user_template_theme_discover',
                extraProperties: ['template_studio_action' => 'themes'],
            ),
            new Get(
                '/user_templates/{name}',
                requirements: ['name' => '.+'],
                description: <<<'MARKDOWN'
                    Read a template and its inheritance chain. Supply its identifier without the file extension,
                    for example content_element/code. Pass the optional "theme" query parameter to select a theme
                    slug; omit it for global user templates. No request body is required.

                    The response contains "identifier", "templates", "operations" and "can_edit". The templates
                    array lists source code and template information from the highest-priority override to the
                    original template. The operations array lists the operation names available for this identifier
                    in the selected theme context. Invoke one using the endpoint documented for that operation. Read
                    the current code before saving: save replaces the complete contents. If can_edit is false, create
                    a user override first when the "create" operation is available.
                    MARKDOWN,
                name: 'contao_api_user_template_read',
            ),
        ];

        foreach ($this->templateStudioOperations as $operation) {
            $operations[] = $this->createOperation($operation);
        }

        foreach ($operations as $name => $operation) {
            $operation = $operation->withParameters([
                ...iterator_to_array($operation->getParameters() ?? []),
                'theme' => new QueryParameter(
                    key: 'theme',
                    schema: ['type' => 'string'],
                    description: 'Optional theme slug. Omit for global user templates; use the same context for subsequent operations.',
                ),
            ]);

            $operations[$name] = $operation
                ->withClass(UserTemplate::class)
                ->withShortName('UserTemplate')
                ->withProvider(UserTemplateStateProvider::class)
                ->withProcessor(UserTemplateStateProcessor::class)
                ->withDefaults(['_scope' => 'backend'])
                ->withStateless(true)
                ->withSecurity("is_granted('ROLE_ADMIN')")
            ;
        }

        return new ResourceMetadataCollection(UserTemplate::class, [
            new ApiResource()
                ->withClass(UserTemplate::class)
                ->withShortName('UserTemplate')
                ->withDescription('Discover and read templates, then execute the available Template Studio operations to create, save, rename or delete user templates. Requires an administrator.')
                ->withDefaults(['_scope' => 'backend'])
                ->withStateless(true)
                ->withSecurity("is_granted('ROLE_ADMIN')")
                ->withOperations(new Operations($operations)),
        ]);
    }

    private function createOperation(object $operation): HttpOperation
    {
        $name = $operation->getName();
        $description = $operation instanceof OperationDescriptionInterface ? $operation->getDescription() : 'Execute the "'.$name.'" template operation.';

        if ('delete' === $name) {
            $description .= <<<'MARKDOWN'
                When using the API, deletion is immediate: no confirmation parameter or request body is required. Pass
                the optional "theme" query parameter to delete a template in a theme.
                MARKDOWN;

            return new Delete(
                uriTemplate: '/user_templates/{name}',
                requirements: ['name' => '.+'],
                description: $description,
                input: false,
                read: false,
                name: 'contao_api_user_template_operation_delete',
                extraProperties: ['template_studio_operation' => $name],
            );
        }

        if ('save' === $name) {
            $description .= <<<'MARKDOWN'
                Send the complete template code as {"code": "..."}. Pass the optional "theme" query parameter for a
                theme template.
                MARKDOWN;

            return new Patch(
                uriTemplate: '/user_templates/{name}',
                requirements: ['name' => '.+'],
                description: $description,
                input: UserTemplateUpdate::class,
                read: false,
                name: 'contao_api_user_template_operation_save',
                extraProperties: ['template_studio_operation' => $name],
            );
        }

        $description .= <<<'MARKDOWN'
            Send the template name in "name" and any arguments in "parameters". Pass the optional "theme" query
            parameter for a theme template. Template names do not include the file extension.
            MARKDOWN;

        $arguments = [
            'uriTemplate' => '/user_template_operations/'.rawurlencode($name),
            'description' => $description,
            'input' => UserTemplateOperation::class,
            'read' => false,
            'name' => 'contao_api_user_template_operation_'.$name,
            'extraProperties' => ['template_studio_operation' => $name],
        ];

        return new Post(...$arguments);
    }
}
