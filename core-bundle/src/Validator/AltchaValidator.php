<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Validator;

use Contao\CoreBundle\Altcha\Altcha as ContaoAltcha;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class AltchaValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ContaoAltcha $altcha,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Altcha) {
            throw new UnexpectedTypeException($constraint, Altcha::class);
        }

        if (!\is_string($value)) {
            $this->context->buildViolation($constraint->message)
                ->addviolation()
            ;

            return;
        }

        if (!$this->altcha->validate($value)) {
            $this->context->buildViolation($constraint->message)
                ->addviolation()
            ;
        }
    }
}
