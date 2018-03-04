<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/project")
 */
class ProjectController extends Controller
{
    /**
     * @Route("/{projectId}/{way}", name="project_index", defaults={"projectId"=0,"way"="inc"},requirements={"projectId"="\d+"})
     */
    public function index(Request $request,$projectId,$way)
    {
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if($projectId != 0){
			$project = $projectRepository->find($projectId);
			if($project){
				if($way == "inc" && $project->getStatus() < 6){
					$project->setStatus($project->getStatus()+1);
				}elseif($way == "dec" && $project->getStatus() > 0){
					$project->setStatus($project->getStatus()-1);
				}
				$em->flush();
			}else{
		 		$this->addFlash('danger','Erreur : projet non trouvé');
			}
			return $this->redirectToRoute('project_index');
		}

		$project = new Project();
		$form = $this->createForm(ProjectType::class,$project);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$project = $form->getData();
			$em->persist($project);
			$em->flush();
		 	$this->addFlash('success','Projet ajouté');
		}

		$projects = $projectRepository->findAll();
		$sortedProjects = array(array(),array(),array(),array(),array(),array(),array(),array());
		foreach($projects as $project){
			$sortedProjects[$project->getStatus()][] = $project;
		}


        return $this->render('project/index.html.twig',array('projects'=>$sortedProjects,'form'=>$form->createView()));
	}

	/**
	 * @Route("/edit/{projectId}",name="project_edit")
	 */
	public function edit(Request $request,$projectId){
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if(!($project = $projectRepository->find($projectId))){
		 	$this->addFlash('danger','Erreur : projet non trouvé');
			return $this->redirectToRoute('project_index');
		}

		$form = $this->createForm(ProjectType::class,$project);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$project = $form->getData();
			$em->persist($project);
			$em->flush();
		 	$this->addFlash('success','Projet mis à jour');
			return $this->redirectToRoute('project_index');
		}
        return $this->render('project/edit.html.twig',array('project'=>$project,'form'=>$form->createView()));
	}

	/**
	 * @Route("/archive/{projectId}",name="project_archive")
	 */
	public function archive(Request $request,$projectId){
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if(!($project = $projectRepository->find($projectId))){
		 	$this->addFlash('danger','Erreur : projet non trouvé');
			return $this->redirectToRoute('project_index');
		}

		if($project->getStatus() == 7){
			$project->setStatus(0);
			$em->flush();
			$this->addFlash('success','Projet désarchivé');
		}else{
			$project->setStatus(7);
			$em->flush();
			$this->addFlash('success','Projet archivé');
		}
		return $this->redirectToRoute('project_index');
	}

	/**
	 * @Route("/move/{projectId}/{newStatus}",name="project_move")
	 */
	public function move(Request $request,$projectId,$newStatus){
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if(!($project = $projectRepository->find($projectId))){
			$arrData = ['success' => false, 'errormsg' => 'Projet non trouvé'];
		}else{
			if($newStatus < 0) $newStatus = 0;
			if($newStatus > 7) $newStatus = 7;
			$project->setStatus($newStatus);
			$em->flush();
			$arrData = ['success' => true];
		}
        return new JsonResponse($arrData);
	}

}
