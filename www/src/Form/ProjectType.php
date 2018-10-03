<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Entity\Project;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		if(!empty($options['statuses'])){
			$builder->add('team',null,array('label'=>'Équipe','choices'=>$options['teams'],'required'=>!empty($options['teams'])));
		}
        $builder
            ->add('reference',null,array('label'=>'Code projet','attr' => array('maxlength' => 10)))
            ->add('name',null,array('label'=>'Nom du projet'))
            ->add('client',null,array('label'=>'Client'));
		if(!empty($options['statuses'])){
			$builder->add('projectManager',null,array('label'=>'Responsable projet','choices'=>$options['users']));
		}
			
		if(!empty($options['statuses'])){
			$builder->add('projectStatus',null,array('label'=>'Statut','choices'=>$options['statuses']));
		}

		$builder
            ->add('billable',null,array('required'=>false,'label'=>'Facturable'))
            ->add('archived',null,array('required'=>false,'label'=>'Archivé'))
            ->add('nbDays',NumberType::class,array('required'=>false,'label'=>'Jours vendus'))
            ->add('comments',TextareaType::class,array('required'=>false,'label'=>'Commentaires'))
            ->add('save',SubmitType::class,array('label'=>'Enregistrer'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
		$resolver->setDefaults([
			"data_class" => Project::class,
			"teams"=>[],
			"statuses"=>[],
			"users"=>[]
        ]);
    }
}
