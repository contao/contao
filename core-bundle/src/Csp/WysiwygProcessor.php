<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Csp;

use Contao\CoreBundle\Csp\RandomClassGenerator\RandomBytesClassGenerator;
use Contao\CoreBundle\Csp\RandomClassGenerator\RandomClassGeneratorInterface;
use Masterminds\HTML5;

class WysiwygProcessor
{
    private RandomClassGeneratorInterface $randomClassGenerator;

    public function __construct(RandomClassGeneratorInterface|null $randomClassGenerator = null)
    {
        $this->randomClassGenerator = $randomClassGenerator ?? new RandomBytesClassGenerator();
    }

    public function processStyles(string $htmlFragment, string $nonce): string
    {
        // Shortcut for performance reasons
        if (!str_contains($htmlFragment, 'style=')) {
            return $htmlFragment;
        }

        $html5 = new HTML5(['disable_html_ns' => true]);
        $fragment = $html5->loadHTMLFragment($htmlFragment);

        $styles = [];
        $this->processChildren($fragment->childNodes, $styles);
        $this->appendStyleNode($fragment, $styles, $nonce);

        return $html5->saveHTML($fragment);
    }

    private function appendStyleNode(\DOMDocumentFragment $fragment, array $styles, string $nonce): void
    {
        $styleElement = $fragment->ownerDocument->createElement('style');
        $styleElement->setAttribute('nonce', $nonce);
        $styleCodes = [];

        foreach ($styles as $className => $style) {
            $styleCodes[] = '.'.$className.' { '.$style.' }';
        }
        $styleElement->nodeValue = implode("\n", $styleCodes);
        $fragment->appendChild($styleElement);
    }

    private function processChildren(\DOMNodeList $nodes, array &$styles): void
    {
        foreach ($nodes as $node) {
            if ($node->hasChildNodes()) {
                $this->processChildren($node->childNodes, $styles);
            }

            if (!$node instanceof \DOMElement || !$node->hasAttribute('style')) {
                continue;
            }

            $className = $this->randomClassGenerator->getRandomClass();
            $styles[$className] = $node->getAttribute('style');
            $node->setAttribute('class', $className);
            $node->removeAttribute('style');
        }
    }
}
