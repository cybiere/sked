<?php

namespace App\Form;

use App\Entity\WorkInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkInputType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('weeknum')
            ->add('mon')
            ->add('tue')
            ->add('wed')
            ->add('thu')
            ->add('fri')
            ->add('comment')
            ->add('locked')
            ->add('user')
            ->add('project')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WorkInput::class,
        ]);
    }
}
