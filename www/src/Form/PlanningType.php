<?php

namespace App\Form;

use App\Entity\Planning;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PlanningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('startDate',null,array('label'=>'Date de début','data' => new \DateTime()))
			->add('startHour',ChoiceType::class,array('label'=>'Heure de début','choices'=>array('Matin'=>'am','Midi'=>'pm')))
            ->add('nbSlices',null,array('label'=>'Nombre de tranches (0,5jh)'))
            ->add('project',null,array('label'=>'Projet'))
            ->add('user',null,array('label'=>'Ressource'))
            ->add('save',SubmitType::class,array('label'=>'Enregistrer'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Planning::class,
        ]);
    }
}
