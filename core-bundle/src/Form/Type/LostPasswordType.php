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

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractContaoFormType<LostPasswordType>
 */
class LostPasswordType extends AbstractContaoFormType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'askForUsername' => true,
            'addCaptcha' => true,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['askForUsername']) {
            $builder
                ->add(
                    'username',
                    TextType::class,
                    [
                        'constraints' => [
                            new NotBlank(),
                        ],
                        'label' => 'tl_member.username.0',
                        'translation_domain' => 'contao_tl_member',
                        'required' => true,
                        'attr' => [
                            'mandatory' => true,
                            'autocomplete' => 'username',
                            'class' => 'text mandatory',
                        ],
                    ],
                )
            ;
        }

        $builder
            ->add(
                'email',
                EmailType::class,
                [
                    'constraints' => [
                        new NotBlank(),
                        new Email(),
                    ],
                    'label' => 'tl_member.email.0',
                    'translation_domain' => 'contao_tl_member',
                    'required' => true,
                    'attr' => [
                        'mandatory' => true,
                        'autocomplete' => 'email',
                        'class' => 'text mandatory',
                    ],
                ],
            )
        ;

        if ($options['addCaptcha']) {
            $builder->add('captcha', AltchaType::class);
        }

        $builder
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'MSC.requestPassword',
                    'attr' => [
                        'class' => 'submit',
                    ],
                ],
            )
        ;
    }
}
