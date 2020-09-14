<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Orm\Annotation;

class AnnotationDumper
{
    public function dump(object $object): string
    {
        return $this->dumpAnnotationFromArray((array) $object);
    }

    /**
     * @param array $item
     * @return string
     *
     * @see https://github.com/thecodingmachine/tdbm-fluid-schema-builder/blob/master/src/DoctrineAnnotationDumper.php
     */
    private function dumpAnnotationFromArray(array $item): string
    {
        if (count($item) === 0) {
            return '';
        }

        return '(' . $this->dumpAnnotationArguments($item, true) . ')';
    }

    private function dumpAnnotationArguments($item, bool $first): string
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
            if ($this->isAssociative($item)) {
                if ($first) {
                    array_walk($item, function (&$value, $key) {
                        $value = $key . '=' . $this->dumpAnnotationArguments($value, false);
                    });
                } else {
                    array_walk($item, function (&$value, $key) {
                        $value = '"' . addslashes($key) . '":' . $this->dumpAnnotationArguments($value, false);
                    });
                }

                $result = implode(', ', $item);
                if (!$first) {
                    $result = '{' . $result . '}';
                }

                return $result;
            } else {
                array_walk($item, function (&$value) {
                    $value = $this->dumpAnnotationArguments($value, false);
                });

                $result = implode(', ', $item);
                if (!$first) {
                    $result = '{' . $result . '}';
                }

                return $result;
            }
        }

        if (is_object($item)) {
            return sprintf('@\\%s%s', get_class($item), $this->dumpAnnotationFromArray((array) $item));
        }

        throw new \RuntimeException('Cannot serialize value in Doctrine annotation.');
    }

    private function isAssociative(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
