<?php

namespace OpenXE\Form\Login;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => 'Passwort',
                'constraints' => [
                    new NotBlank(message: 'Passwort darf nicht leer sein.'),
                    new Length(
                        min: 8,
                        minMessage: 'Passwort muss mindestens {{ limit }} Zeichen lang sein.',
                    ),
                ]
            ])
            ->add('password2', PasswordType::class, [
                'label' => 'Passwort wiederholen',
                'constraints' => [
                    new NotBlank(message: 'Passwort wiederholen darf nicht leer sein.'),
                    new Callback(function ($value, ExecutionContextInterface $context) {
                        $password = $context->getRoot()->get('password')->getData();
                        if ($password !== $value) {
                            $context->buildViolation('Die Passwörter müssen übereinstimmen.')
                                ->addViolation();
                        }
                    })
                ]
            ])
            ->add('submit', SubmitType::class, ['label' => 'Passwort ändern'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
