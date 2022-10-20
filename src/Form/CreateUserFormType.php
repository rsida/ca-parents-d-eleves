<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CreateUserFormType extends AbstractType
{
    private Security $security;
    private array $hierarchyRoles;

    public function __construct(Security $security, array $hierarchyRoles)
    {
        $this->security = $security;
        $this->hierarchyRoles = $hierarchyRoles;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email')
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 5,
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'multiple' => true,
                'choices' => array_combine(array_keys($this->hierarchyRoles), array_keys($this->hierarchyRoles)),
                'choice_filter' => function ($choice) {
                    return $this->security->isGranted($choice);
                },
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => array_combine(User::GENDERS, User::GENDERS),
            ])
            ->add('firstName')
            ->add('lastName')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
