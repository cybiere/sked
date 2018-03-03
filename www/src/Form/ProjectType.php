<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Entity\Project;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('reference',null,array('label'=>'Code projet','attr' => array('maxlength' => 10)))
            ->add('name',null,array('label'=>'Nom du projet'))
            ->add('client',null,array('label'=>'Client'))
			->add('status',ChoiceType::class,array('label'=>'Statut',
												   'choices'=>array(
														'Non validé'=>0,
														'Lancement'=>1,
														'En cours'=>2,
														'Rapport'=>3,
														'Relecture'=>4,
														'Restitution'=>5,
														'Facturation'=>6,
														'Archivé'=>7
			)))
            ->add('nbDays',null,array('required'=>false,'label'=>'Jours vendus'))
            ->add('comments',TextareaType::class,array('required'=>false,'label'=>'Commentaires'))
            ->add('save',SubmitType::class,array('label'=>'Enregistrer'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
		$resolver->setDefaults([
			"data_class" => Project::class
        ]);
    }
}
