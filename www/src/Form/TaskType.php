<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		if($options['project']!=null){
			$builder->add('project',EntityType::Class,array('label'=>'Projet','class'=>Project::class,'disabled'=>true,'data'=>$options['project']));
		}else{
			$builder->add('project',null,array('label'=>'Projet'));
		}
        $builder
            ->add('name',null,array('label'=>'Nom de la tâche'))
            ->add('assignedTo',null,array('label'=>'Assignée à','choices'=>$options['users']))
            ->add('nbDays',NumberType::class,array('required'=>false,'label'=>'Jours vendus'))
            ->add('comments',TextareaType::class,array('required'=>false,'label'=>'Commentaires'))
            ->add('save',SubmitType::class,array('label'=>'Enregistrer'))
		;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
			'data_class' => Task::class,
			'project' => null,
			'users' => [],
        ]);
    }
}
