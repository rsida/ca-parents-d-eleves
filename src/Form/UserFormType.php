<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserFormType extends AbstractType
{
    private Security $security;
    private TranslatorInterface $translator;
    private array $hierarchyRoles;

    public function __construct(Security $security, TranslatorInterface $translator, array $hierarchyRoles)
    {
        $this->security = $security;
        $this->translator = $translator;
        $this->hierarchyRoles = $hierarchyRoles;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('roles', ChoiceType::class, [
                'multiple' => true,
                'choices' => array_combine(array_keys($this->hierarchyRoles), array_keys($this->hierarchyRoles)),
                'choice_filter' => function ($choice) {
                    return $this->security->isGranted($choice);
                },
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => array_flip(User::TRANS_GENDERS),
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
