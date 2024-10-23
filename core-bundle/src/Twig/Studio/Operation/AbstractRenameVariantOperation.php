<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
abstract class AbstractRenameVariantOperation extends AbstractOperation
{
    public function canExecute(TemplateContext $context): bool
    {
        return 1 === preg_match('%^'.preg_quote($this->getPrefix(), '%').'/[^/]+/.+$%', $context->getIdentifier());
    }

    public function execute(Request $request, TemplateContext $context): Response|null
    {
        preg_match('%^('.preg_quote($this->getPrefix(), '%').'/.+)/(.+)$%', $context->getIdentifier(), $matches);
        [, $baseIdentifier, $oldFragment] = $matches;

        // Show a confirmation dialog
        if (!$identifierFragment = $request->request->getString('identifier_fragment')) {
            return $this->render('@Contao/backend/template_studio/operation/create_or_rename_variant.stream.html.twig', [
                'operation' => $this->getName(),
                'operation_type' => 'rename',
                'identifier' => $baseIdentifier,
                'extension' => $context->getExtension(),
                'suggested_identifier_fragment' => $oldFragment,
                'allowed_identifier_fragment_pattern' => $this->buildAllowedIdentifierFragmentsPattern($baseIdentifier),
            ]);
        }

        $newIdentifier = "$baseIdentifier/$identifierFragment";
        $newStoragePath = "$newIdentifier.{$context->getExtension()}";

        if ($this->getUserTemplatesStorage()->fileExists($newStoragePath)) {
            return $this->error($context, 'message.rename_variant.error');
        }

        // Rename the variant template file
        $this->getUserTemplatesStorage()->move($context->getUserTemplatesStoragePath(), $newStoragePath);

        $this->refreshTemplateHierarchy();

        return $this->render('@Contao/backend/template_studio/operation/rename_variant_result.stream.html.twig', [
            'old_identifier' => $context->getIdentifier(),
            'new_identifier' => $newIdentifier,
        ]);
    }

    /**
     * Return the template identifier prefix this operation is targeting (e.g.
     * "content_element").
     */
    abstract protected function getPrefix(): string;

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
            implode('|', array_map(preg_quote(...), $existingVariantNames)),
        );
    }
}
