<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('startAt')
            ->add('endAt')
            ->add('participants', EntityType::class, [
                'class' => User::class,
                'multiple' => true,
                'choice_label' => 'email',
                'query_builder' => function(UserRepository $userRepository) {
                    return $userRepository->createFindByCriteriaQueryBuilder();
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
