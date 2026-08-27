<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Schema;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Validator as ContaoValidator;

final class DataContainerSchemaFactory
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    /**
     * @return array{
     *     type: 'object',
     *     properties: array<string, array<string, mixed>>,
     *     required?: list<string>,
     *     additionalProperties: bool
     * }
     */
    public function create(string $table): array
    {
        $this->framework->initialize();
        $this->framework->getAdapter(Controller::class)->loadDataContainer($table);

        $properties = [];
        $required = [];

        foreach ($GLOBALS['TL_DCA'][$table]['fields'] ?? [] as $fieldName => $config) {
            $schema = $this->createFieldSchema((string) $fieldName, \is_array($config) ? $config : []);

            if ([] === $schema) {
                continue;
            }

            $properties[$fieldName] = $schema;

            if (($config['eval']['mandatory'] ?? false) === true) {
                $required[] = $fieldName;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
            'additionalProperties' => false,
        ];

        if ($required) {
            $schema['required'] = array_values(array_unique($required));
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function createFieldSchema(string $fieldName, array $config): array
    {
        if ('id' === $fieldName) {
            return [
                'type' => 'integer',
                'readOnly' => true,
            ];
        }

        $schema = [];
        $sql = $config['sql'] ?? [];
        $eval = $config['eval'] ?? [];
        $inputType = $config['inputType'] ?? null;

        if (\is_string($sql)) {
            $sql = [];
        }

        $type = $this->guessType($config, $sql);

        if (null !== $type) {
            $schema['type'] = $type;
        }

        if ('string' === ($schema['type'] ?? null) && isset($sql['length']) && \is_int($sql['length'])) {
            $schema['maxLength'] = $sql['length'];
        }

        if ('string' === ($schema['type'] ?? null) && isset($eval['maxlength']) && is_numeric($eval['maxlength'])) {
            $schema['maxLength'] = (int) $eval['maxlength'];
        }

        if ('string' === ($schema['type'] ?? null) && isset($eval['minlength']) && is_numeric($eval['minlength'])) {
            $schema['minLength'] = (int) $eval['minlength'];
        }

        if ($choices = $this->normalizeChoices($config['options'] ?? null)) {
            $schema['enum'] = $choices;
        }

        if ('text' === ($sql['type'] ?? null) && isset($sql['length']) && \is_int($sql['length'])) {
            $schema['maxLength'] = $sql['length'];
        }

        if ('checkbox' === $inputType || 'boolean' === ($sql['type'] ?? null)) {
            $schema['type'] = 'boolean';
        }

        if (isset($eval['rgxp'])) {
            $schema += $this->getFormatForRgxp((string) $eval['rgxp']);
        }

        if (isset($sql['default']) && !isset($schema['default'])) {
            $schema['default'] = $sql['default'];
        }

        if (isset($eval['multiple']) && true === $eval['multiple']) {
            $schema = [
                'type' => 'array',
                'items' => $schema ?: ['type' => 'string'],
            ];
        }

        if (isset($eval['maxlength']) && 'integer' === ($schema['type'] ?? null)) {
            $schema['type'] = 'integer';
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $sql
     */
    private function guessType(array $config, array $sql): string|null
    {
        $inputType = $config['inputType'] ?? null;

        if ('checkbox' === $inputType) {
            return 'boolean';
        }

        if (isset($sql['type'])) {
            return match ($sql['type']) {
                'boolean' => 'boolean',
                'integer', 'smallint', 'bigint' => 'integer',
                'decimal', 'float', 'double', 'numeric', 'real' => 'number',
                'array', 'json' => 'array',
                default => 'string',
            };
        }

        if (isset($config['options']) && \is_array($config['options'])) {
            return 'string';
        }

        return null;
    }

    /**
     * @return list<scalar>
     */
    private function normalizeChoices(mixed $choices): array
    {
        if (!\is_array($choices)) {
            return [];
        }

        $normalized = [];

        foreach ($choices as $choice) {
            if (\is_array($choice)) {
                continue;
            }

            if (null === $choice || \is_object($choice)) {
                continue;
            }

            $normalized[] = $choice;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function getFormatForRgxp(string $rgxp): array
    {
        return match ($rgxp) {
            'email' => ['format' => 'email'],
            'url' => ['format' => 'uri'],
            'digit' => ['pattern' => ContaoValidator::REGEXP_DIGIT],
            'alpha' => ['pattern' => ContaoValidator::REGEXP_ALPHA],
            'alnum' => ['pattern' => ContaoValidator::REGEXP_ALNUM],
            default => [],
        };
    }
}
