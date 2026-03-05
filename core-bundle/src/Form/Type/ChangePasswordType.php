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
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractContaoType<ChangePasswordType>
 */
class ChangePasswordType extends AbstractContaoType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'oldpassword',
                PasswordType::class,
                [
                    'constraints' => [
                        new UserPassword(message: 'MSC.oldPasswordWrong'),
                    ],
                    'label' => 'MSC.oldPassword',
                    'required' => true,
                    'attr' => [
                        'mandatory' => true,
                        'autocomplete' => 'current-password',
                        'class' => 'text password mandatory',
                    ],
                ],
            )
            ->add(
                'newpassword',
                PasswordType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new Length(min: 8),
                    ],
                    'label' => 'MSC.newPassword',
                    'required' => true,
                    'attr' => [
                        'mandatory' => true,
                        'autocomplete' => 'new-password',
                        'class' => 'text password mandatory',
                    ],
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'MSC.changePassword',
                    'attr' => [
                        'class' => 'submit',
                    ],
                ],
            )
        ;
    }
}
