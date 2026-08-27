<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Validator\Constraints;

use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator as JsonSchemaValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class DataContainerRecordSchemaValidator extends ConstraintValidator
{
    public function __construct(
        private readonly DataContainerSchemaFactory $schemaFactory,
        private readonly JsonSchemaValidator $validator,
    ) {
        $this->validator->setMaxErrors(100);
        $this->validator->setStopAtFirstError(false);
    }

    /**
     * @param DataContainerRecord|mixed $value
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DataContainerRecordSchema || !$value instanceof DataContainerRecord) {
            return;
        }

        $schema = $this->toSchemaObject($this->schemaFactory->create($value->table));
        $data = $this->toJsonValue($value->data, true);
        $result = $this->validator->validate($data, $schema);

        if ($result->isValid()) {
            return;
        }

        $error = $result->error();

        if (!$error) {
            return;
        }

        $this->addViolations($error, new ErrorFormatter());
    }

    private function addViolations(ValidationError $error, ErrorFormatter $formatter): void
    {
        $subErrors = $error->subErrors();

        if ([] !== $subErrors) {
            $hasPropertiesSubError = false;

            foreach ($subErrors as $subError) {
                if ('properties' === $subError->keyword()) {
                    $hasPropertiesSubError = true;
                    break;
                }
            }

            foreach ($subErrors as $subError) {
                if ($hasPropertiesSubError && 'additionalProperties' === $subError->keyword()) {
                    continue;
                }

                $this->addViolations($subError, $formatter);
            }

            return;
        }

        $path = $this->formatPath($error->data()->fullPath());
        $message = $formatter->formatErrorMessage($error);

        if ('' !== $path) {
            $message = \sprintf('Field "%s": %s', $path, $message);
        }

        $builder = $this->context->buildViolation($message);

        if ('' !== $path) {
            $builder->atPath($path);
        }

        $builder->addViolation();
    }

    /**
     * @param array<int, string|int> $path
     */
    private function formatPath(array $path): string
    {
        return implode('.', array_map(static fn (int|string $part): string => (string) $part, $path));
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function toSchemaObject(array $schema): object
    {
        return json_decode(json_encode($schema, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Converts PHP arrays into JSON-compatible arrays or objects.
     */
    private function toJsonValue(mixed $value, bool $forceObject = false): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        if (!$forceObject && array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->toJsonValue($item), $value);
        }

        $object = new \stdClass();

        foreach ($value as $key => $item) {
            $object->{$key} = $this->toJsonValue($item);
        }

        return $object;
    }
}
