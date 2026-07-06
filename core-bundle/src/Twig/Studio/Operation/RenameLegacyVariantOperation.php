<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForTemplateStudioElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[AsOperationForTemplateStudioElement]
final class RenameLegacyVariantOperation extends AbstractOperation
{
    public function canExecute(OperationContext $context): bool
    {
        if ($context->isThemeContext()) {
            return false;
        }

        if (str_contains($context->getIdentifier(), '/') || !str_contains($context->getIdentifier(), '_')) {
            return false;
        }

        return $this->userTemplateExists($context, true);
    }

    public function execute(Request $request, OperationContext $context): Response
    {
        [$baseIdentifier, $oldFragment] = explode('_', $context->getIdentifier(), 2);

        // Show a confirmation dialog
        if (!$identifierFragment = $request->request->getString('identifier_fragment')) {
            return $this->render('@Contao/backend/template_studio/operation/create_or_rename_variant.stream.html.twig', [
                'operation' => $this->getName(),
                'operation_type' => 'rename',
                'identifier' => $baseIdentifier,
                'extension' => $context->getExtension(),
                'separator' => '_',
                'suggested_identifier_fragment' => $oldFragment,
                'allowed_identifier_fragment_pattern' => $this->buildAllowedIdentifierFragmentsPattern($baseIdentifier),
            ]);
        }

        $newIdentifier = "{$baseIdentifier}_{$identifierFragment}";
        $newStoragePath = "$newIdentifier.{$context->getExtension()}";

        if ($this->getUserTemplatesStorage()->fileExists($newStoragePath)) {
            return $this->error($context, 'template_studio.message.rename_variant.error');
        }

        // Rename the variant template file
        $this->getUserTemplatesStorage()->move($context->getUserTemplatesStoragePath(), $newStoragePath);

        $this->invalidateTemplateCache($context);
        $this->refreshTemplateHierarchy();

        return $this->render('@Contao/backend/template_studio/operation/rename_variant_result.stream.html.twig', [
            'old_identifier' => $context->getIdentifier(),
            'new_identifier' => $newIdentifier,
        ]);
    }

    private function buildAllowedIdentifierFragmentsPattern(string $identifier): string
    {
        $existingVariantNames = array_map(
            static fn (string $variantIdentifier): string => substr($variantIdentifier, \strlen($identifier) + 1),
            array_keys(
                iterator_to_array(
                    $this->getTwigFinder()
                        ->identifier($identifier)
                        ->withVariants(true),
                ),
            ),
        );

        // Disallow selecting a name of an existing variant
        return \sprintf(
            '^(?!(%s)$).*',
            implode('|', $existingVariantNames),
        );
    }
}
