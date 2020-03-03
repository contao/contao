<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Parser;

class ImportedNamespaceParser
{
    /**
     * @see https://gist.github.com/Zeronights/7b7d90fcf8d4daf9db0c
     * @param \ReflectionClass $reflectionClass
     * @return array
     * @throws \ReflectionException
     */
    public function getNamespaces(\ReflectionClass $reflectionClass): array
    {
        $source = file_get_contents($reflectionClass->getFileName());
        $tokens = token_get_all($source);

        $builtNamespace = '';
        $buildingNamespace = false;
        $matchedNamespace = false;

        $useStatements = [];
        $record = false;
        $currentUse = [
            'class' => '',
            'as' => '',
        ];

        foreach ($tokens as $token) {
            if ($token[0] === T_NAMESPACE) {
                $buildingNamespace = true;

                if ($matchedNamespace) {
                    break;
                }
            }

            if ($buildingNamespace) {
                if ($token === ';') {
                    $buildingNamespace = false;
                    continue;
                }

                switch ($token[0]) {
                    case T_STRING:
                    case T_NS_SEPARATOR:
                        $builtNamespace .= $token[1];
                        break;
                }

                continue;
            }

            if ($token === ';' || !is_array($token)) {
                if ($record) {
                    $useStatements[] = $currentUse;
                    $record = false;
                    $currentUse = [
                        'class' => '',
                        'as' => '',
                    ];
                }

                continue;
            }

            if ($token[0] === T_CLASS) {
                break;
            }

            if (strcasecmp($builtNamespace, $reflectionClass->getNamespaceName()) === 0) {
                $matchedNamespace = true;
            }

            if ($matchedNamespace) {

                if ($token[0] === T_USE) {
                    $record = 'class';
                }

                if ($token[0] === T_AS) {
                    $record = 'as';
                }

                if ($record) {
                    switch ($token[0]) {
                        case T_STRING:
                        case T_NS_SEPARATOR:

                            if ($record) {
                                $currentUse[$record] .= $token[1];
                            }

                            break;
                    }
                }
            }

            if ($token[2] >= $reflectionClass->getStartLine()) {
                break;
            }
        }

        foreach ($useStatements as $key => $useStatement) {
            if ('' !== $useStatement['as']) {
                continue;
            }

            $reflectionUse = new \ReflectionClass($useStatement['class']);
            $useStatements[$key]['as'] = $reflectionUse->getShortName();
        }

        return $useStatements;
    }
}
