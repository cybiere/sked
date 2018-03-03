<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ProjectController extends Controller
{
    /**
     * @Route("/project", name="project")
     */
    public function index(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		$project = new Project();
		$form = $this->createForm(ProjectType::class,$project);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$project = $form->getData();
			$em->persist($project);
			$em->flush();
		}

		$projects = $projectRepository->findAll();
		$sortedProjects = array(
			array(),
			array(),
			array(),
			array(),
			array(),
			array(),
			array(),
			array(),
		);
		foreach($projects as $project){
			$sortedProjects[$project->getStatus()][] = $project;
		}

        return $this->render('project/index.html.twig',array('projects'=>$sortedProjects,'form'=>$form->createView()));
    }
}
