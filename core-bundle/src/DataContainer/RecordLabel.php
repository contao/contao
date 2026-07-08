<?php

declare(strict_types=1);

namespace Contao\CoreBundle\DataContainer;

use Contao\StringUtil;

final class RecordLabel implements \Stringable
{
    /**
     * @var list<string>|null
     */
    public array|null $columns = null;

    public string|null $htmlLabel = null;

    /**
     * @var list<string>|null
     */
    public array|null $htmlColumns = null;

    public string|null $htmlPreview = null;

    public string|null $state = null;

    public function __construct(public string $label)
    {
    }

    /**
     * @deprecated Deprecated since Contao 6.0, to be removed in Contao 7.
     */
    public function __toString(): string
    {
        trigger_deprecation('contao/core-bundle', '6.0', 'Using "%s()" is deprecated and will no longer work in Contao 7.', __METHOD__);

        return $this->htmlLabel ?? StringUtil::specialchars($this->label);
    }

    public static function fromCallback(self|array|string $callbackReturnValue, bool $asColumns = false): self
    {
        if ($callbackReturnValue instanceof self) {
            return $callbackReturnValue;
        }

        if (!$asColumns) {
            $label = new self(((array) $callbackReturnValue)[0]);

            if (\is_array($callbackReturnValue)) {
                $label->htmlPreview = $callbackReturnValue[1] ?? null;
                $label->state = $callbackReturnValue[2] ?? null;
            }
        } else {
            $callbackReturnValue = StringUtil::decodeEntities($callbackReturnValue);
            $label = new self(implode(', ', (array) $callbackReturnValue));

            if (\is_array($callbackReturnValue)) {
                $label->columns = $callbackReturnValue;
            }
        }

        return $label;
    }

    public static function fromHtml(array|string $html): self
    {
        $label = new self(trim(StringUtil::decodeEntities(strip_tags(implode(', ', (array) $html)))));

        if (\is_string($html)) {
            $label->htmlLabel = $html;
        } else {
            $label->htmlColumns = $html;
        }

        return $label;
    }
}
