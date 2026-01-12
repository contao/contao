<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

/**
 * @extends AbstractContaoFormType<CloseAccountType>
 */
class CloseAccountType extends AbstractContaoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'password',
                PasswordType::class,
                [
                    'constraints' => [
                        new UserPassword(message: 'ERR.invalidPass'),
                    ],
                    'label' => 'MSC.password.0',
                    'required' => true,
                    'attr' => [
                        'mandatory' => true,
                        'autocomplete' => 'current-password',
                        'class' => 'text password mandatory',
                    ],
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'MSC.closeAccount',
                    'attr' => [
                        'class' => 'submit',
                    ],
                ],
            )
        ;
    }
}
