<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Orm\Annotation;

use Doctrine\ORM\Mapping\Annotation;

class AnnotationDumper
{
    public function dump(object $object): string
    {
        return $this->exportValues((array) $object);
    }

    // https://github.com/thecodingmachine/tdbm-fluid-schema-builder/blob/master/src/DoctrineAnnotationDumper.php
    private function exportValues($item): string
    {
        if ($item === null) {
            return '';
        }
        if ($item === []) {
            return '';
        }

        return '(' . $this->innerExportValues($item, true) . ')';
    }

    private function innerExportValues($item, bool $first): string
    {
        if ($item === null) {
            return 'null';
        }
        if (is_string($item)) {
            return '"' . str_replace('"', '""', $item) . '"';
        }
        if (is_numeric($item)) {
            return (string) $item;
        }
        if (is_bool($item)) {
            return $item ? 'true' : 'false';
        }
        if (is_array($item)) {
            if ($this->isAssoc($item)) {
                if ($first) {
                    array_walk($item, function (&$value, $key) {
                        $value = $key . ' = ' . $this->innerExportValues($value, false);
                    });
                } else {
                    array_walk($item, function (&$value, $key) {
                        $value = '"' . addslashes($key) . '":' . $this->innerExportValues($value, false);
                    });
                }
                $result = implode(', ', $item);
                if (!$first) {
                    $result = '{' . $result . '}';
                }

                return $result;
            } else {
                array_walk($item, function (&$value, $key) {
                    $value = $this->innerExportValues($value, false);
                });
                $result = implode(', ', $item);
                if (!$first) {
                    $result = '{' . $result . '}';
                }

                return $result;
            }
        }
        if (is_object($item)) {
            return sprintf('@%s%s', get_class($item), $this->exportValues((array) $item));
        }

        throw new \RuntimeException('Cannot serialize value in Doctrine annotation.');
    }

    private function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
