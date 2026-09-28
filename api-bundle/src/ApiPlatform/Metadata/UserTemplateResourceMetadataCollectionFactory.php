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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\ApiPlatform\State\UserTemplateStateProcessor;
use Contao\ApiBundle\ApiPlatform\State\UserTemplateStateProvider;
use Contao\ApiBundle\Dto\UserTemplateOperation;
use Contao\ApiBundle\Resource\UserTemplate;
use Contao\CoreBundle\Twig\Studio\Operation\OperationDescriptionInterface;

final class UserTemplateResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly iterable $templateStudioOperations,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        if (UserTemplate::class !== $resourceClass) {
            return $this->decorated->create($resourceClass);
        }

        $operations = [
            new Get(
                '/user_template',
                description: <<<'MARKDOWN'
                    Discover available templates. The response contains a "tree" with the template hierarchy.
                    Use a template identifier from this tree to read its source and available operations via
                    GET /user_template/{identifier}. Identifiers do not include the file extension.
                    Pass the optional "theme" query parameter to select a theme slug; omit it for global user
                    templates. Use the same theme context when reading or modifying a template. No request body
                    is required.
                    MARKDOWN,
                name: 'contao_api_user_template_discover',
            ),
            new Get(
                '/user_template/{identifier}',
                requirements: ['identifier' => '.+'],
                description: <<<'MARKDOWN'
                    Read a template and its inheritance chain. Supply its identifier without the file extension,
                    for example content_element/code. Pass the optional "theme" query parameter to select a theme
                    slug; omit it for global user templates. No request body is required.

                    The response contains "identifier", "templates", "operations" and "can_edit". The templates
                    array lists source code and template information from the highest-priority override to the
                    original template. The operations array lists the operation names available for this identifier
                    in the selected theme context. Invoke one with POST /user_template/{identifier}/{operation},
                    following that operation's parameter and confirmation instructions. Read the current code
                    before saving: save replaces the complete contents. If can_edit is false, create a user override
                    first when the "create" operation is available.
                    MARKDOWN,
                name: 'contao_api_user_template_read',
            ),
        ];

        foreach ($this->templateStudioOperations as $operation) {
            $name = $operation->getName();
            $description = $operation instanceof OperationDescriptionInterface ? $operation->getDescription() : 'Execute the "'.$name.'" template operation.';
            $description .= <<<'MARKDOWN'
                Send a JSON object with the operation parameters nested under "parameters", for example {"parameters":
                {"code": "..."}} for save. For operations without parameters, send {}. Set Content-Type:
                application/ld+json when using JSON-LD; Accept alone does not specify the request body format.

                The URL identifier is the template identifier without its file extension, for example
                content_element/code/compact. An optional top-level "theme" string selects the theme slug; omit it or
                use null for the global user templates. Variant creation and renaming require the global context.
                Responses may describe an intermediary step rather than a completed change; inspect the returned fields
                before assuming the operation has completed.
                MARKDOWN;

            $operations[] = new Post(
                uriTemplate: '/user_template/{identifier}/'.rawurlencode($name),
                requirements: ['identifier' => '.+'],
                description: $description,
                input: UserTemplateOperation::class,
                read: false,
                name: 'contao_api_user_template_operation_'.$name,
                extraProperties: ['template_studio_operation' => $name],
            );
        }

        foreach ($operations as $name => $operation) {
            if ($operation instanceof Get) {
                $operation = $operation->withParameters([
                    'theme' => new QueryParameter(
                        schema: ['type' => 'string'],
                        description: 'Optional theme slug. Omit for global user templates; use the same context for subsequent operations.',
                    ),
                ]);
            }

            $operations[$name] = $operation
                ->withClass(UserTemplate::class)
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
                ->withDescription('Discover and read templates, then execute the available Template Studio operations to create, save, rename or delete user templates. Requires an administrator.')
                ->withDefaults(['_scope' => 'backend'])
                ->withStateless(true)
                ->withSecurity("is_granted('ROLE_ADMIN')")
                ->withOperations(new Operations($operations)),
        ]);
    }
}
