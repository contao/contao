<?php

namespace Symfony\Component\HttpFoundation;

// Forward compatibility with symfony/http-foundation >=5.0
if (!class_exists(InputBag::class)) {
    class InputBag extends ParameterBag
    {
        public function all()
        {
            $key = \func_num_args() > 0 ? func_get_arg(0) : null;

            if ($key) {
                return $this->get($key);
            }

            return parent::all();
        }
    }
}

namespace Symfony\Component\Translation;

use Symfony\Contracts\Translation\TranslatorInterface as BaseTranslatorInterface;

// Backwards compatibility with symfony/translation <5.0
if (!interface_exists(TranslatorInterface::class)) {
    interface TranslatorInterface extends BaseTranslatorInterface
    {
    }
}
