<?php

namespace App\Form;

use App\Entity\GameCharacter;
use App\Entity\GameGuild;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class GameCharacterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Character name is required.',
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Character name must be at least {{ limit }} characters long.',
                        'maxMessage' => 'Character name cannot be longer than {{ limit }} characters.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9\s\-_]+$/u',
                        'message' => 'Character name contains invalid characters.',
                    ]),
                ],
            ])
            ->add('class', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Class is required.',
                    ]),
                ],
            ])
            ->add('classSpec', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Class specialization is required.',
                    ]),
                ],
            ])
            ->add('classSpecSecondary', TextType::class, [
                'required' => false,
            ])
            ->add('role', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Role is required.',
                    ]),
                ],
            ])
            ->add('guild', EntityType::class, [
                'class' => GameGuild::class,
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Guild is required.',
                    ]),
                ],
            ])
            ->add('userPlayer', EntityType::class, [
                'class' => User::class,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameCharacter::class,
        ]);
    }
}
