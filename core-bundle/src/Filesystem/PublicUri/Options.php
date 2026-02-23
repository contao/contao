<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\PublicUri;

class Options
{
    public const OPTION_CONTENT_DISPOSITION_TYPE = 'content_disposition_type';

    public const OPTION_ADD_VERSION_QUERY_PARAMETER = 'add_version_parameter';

    public const OPTION_TEMPORARY_ACCESS_INFORMATION = 'temporary_access_information';

    /**
     * @param array<string, mixed> $options
     */
    private function __construct(private array $options = [])
    {
    }

    public static function create(): self
    {
        return new self([
            // Attach inline by default
            self::OPTION_CONTENT_DISPOSITION_TYPE => new ContentDispositionOption(true),
            // Versionize by default
            self::OPTION_ADD_VERSION_QUERY_PARAMETER => true,
        ]);
    }

    public function get(string $property, mixed $default = null): mixed
    {
        return $this->options[$property] ?? $default;
    }

    public function toArray(): array
    {
        return $this->options;
    }

    public function withSetting(string $property, mixed $setting): self
    {
        $options = $this->options;
        $options[$property] = $setting;

        return new self($options);
    }

    public function withoutSettings(string ...$settings): self
    {
        return new self(array_diff_key($this->options, array_flip($settings)));
    }
}
