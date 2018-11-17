<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectStatus;
use App\Entity\User;
use App\Entity\Team;
use App\Entity\Planning;
use App\Form\ProjectType;
use App\Entity\Task;
use App\Form\TaskType;

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
	 * @Route("/", name="project_index")
	 */
	public function index(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		if($me->isAdmin()){
			$managedTeams = $teamRepository->findAll();
		}else{
			$managedTeams = $me->getManagedTeams();
		}
		$managedUsers = $userRepository->findAll();
		if(!$me->isAdmin()){
			foreach($managedUsers as $key => $user){
				if(!$me->canAdmin($user)){
					unset($managedUsers[$key]);
				}
			}
		}

		$project = new Project();
		$form = $this->createForm(ProjectType::class,$project,['teams'=>$managedTeams,'users'=>$managedUsers]);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$project = $form->getData();

			if(!$me->canAdmin($project)){
				throw $this->createNotFoundException("Cette page n'existe pas");
			}
			$em->persist($project);
			$em->flush();
			$this->addFlash('success','Projet ajouté');
		}

		if($this->get('session')->get('user')->isAdmin()){
			$myTeams = $teamRepository->findAll();
		}else{
			$myTeams = array_unique(array_merge($me->getTeams()->toArray(),$me->getManagedTeams()));
			$teamlessProjects = [];
		}
		$teamlessProjects = $projectRepository->findByTeam(NULL);

		return $this->render('project/index.html.twig',array('teams'=>$myTeams,'form'=>$form->createView(),'me'=>$me,'teamlessProjects'=>$teamlessProjects));
	}

	/**
	 * @Route("/{projectId}", name="project_view", defaults={"projectId"=0},requirements={"projectId"="\d+"})
	 */
	public function view(Request $request, $projectId){
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($project = $projectRepository->find($projectId))){
			$this->addFlash('danger','Erreur : projet non trouvé');
			$referer = $request->headers->get('referer');
			return $this->redirect($referer);
		}

		$managedUsers = $userRepository->findAll();
		if(!$me->isAdmin()){
			foreach($managedUsers as $key => $user){
				if(!$me->canAdmin($user)){
					unset($managedUsers[$key]);
				}
			}
		}
		$task = new Task();
		$form = $this->createForm(TaskType::class,$task,["project"=>$project,"users"=>$managedUsers]);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			if($me->canAdmin($project)){
				$task->setProject($project);
				$em->persist($task);
				$em->flush();
				$this->addFlash('success','Tâche enregistrée');
				$task = new Task();
				$form = $this->createForm(TaskType::class,$task,['project'=>$project]);
			}else{
				throw $this->createNotFoundException("Cette page n'existe pas");
			}
		}

		$plannings = $planningRepository->findBy(
			array('project'=>$projectId),
			array('startDate'=>'ASC','startHour'=>'ASC'));

		$users=array();
		$userIds=array();
		foreach($plannings as $planning){
			if(!in_array($planning->getUser()->getId(),$userIds)){
				$users[] = $planning->getUser();
				$userIds[] = $planning->getUser()->getId();
			}
		}
		if(isset($plannings[0])){
			$startDateObj = $plannings[0]->getStartDate();
		}else{
			$startDateObj = new \DateTime();
		}
		$baseYear = intval($startDateObj->format('Y'));
		$holidays = array();
		for($i=-1;$i<=1;$i++){
			$year=$baseYear+$i;
			$easterDate  = \easter_date($year);
			$easterDay   = date('j', $easterDate);
			$easterMonth = date('n', $easterDate);
			$easterYear  = date('Y', $easterDate);

			// Dates fixes
			$holidays[] = mktime(0, 0, 0, 1,  1,  $year);  // 1er janvier
			$holidays[] = mktime(0, 0, 0, 5,  1,  $year);  // Fête du travail
			$holidays[] = mktime(0, 0, 0, 5,  8,  $year);  // Victoire des alliés
			$holidays[] = mktime(0, 0, 0, 7,  14, $year);  // Fête nationale
			$holidays[] = mktime(0, 0, 0, 8,  15, $year);  // Assomption
			$holidays[] = mktime(0, 0, 0, 11, 1,  $year);  // Toussaint
			$holidays[] = mktime(0, 0, 0, 11, 11, $year);  // Armistice
			$holidays[] = mktime(0, 0, 0, 12, 25, $year);  // Noel

			// Dates variables
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear);
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear);
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear);
		}
		sort($holidays);

		return $this->render('project/view.html.twig',array('project'=>$project,'plannings'=>$plannings,'users'=>$users,'holidays'=>$holidays,'form'=>$form->createView(),'me'=>$me));
	}


	/**
	 * @Route("/edit/{projectId}",name="project_edit")
	 */
	public function edit(Request $request,$projectId){
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if(!($project = $projectRepository->find($projectId))){
			$this->addFlash('danger','Erreur : projet non trouvé');
			$referer = $request->headers->get('referer');
			return $this->redirect($referer);
		}

		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!$me->canAdmin($project)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		if($me->isAdmin()){
			$managedTeams = $teamRepository->findAll();
		}else{
			$managedTeams = $me->getManagedTeams();
		}

		$managedUsers = $userRepository->findAll();
		if(!$me->isAdmin()){
			foreach($managedUsers as $key => $user){
				if(!$me->canAdmin($user)){
					unset($managedUsers[$key]);
				}
			}
		}

		$form = $this->createForm(ProjectType::class,$project,['teams'=>$managedTeams,'users'=>$managedUsers]);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$project = $form->getData();

			if(!$me->canAdmin($project)){
				throw $this->createNotFoundException("Cette page n'existe pas");
			}

			$em->persist($project);
			$em->flush();
			$this->addFlash('success','Projet mis à jour');
			return $this->redirectToRoute('project_view',['projectId'=>$projectId]);
		}
		return $this->render('project/edit.html.twig',array('project'=>$project,'form'=>$form->createView()));
	}

	/**
	 * @Route("/archive/{projectId}",name="project_archive")
	 */
	public function archive(Request $request,$projectId){
		$referer = $request->headers->get('referer');
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if(!($project = $projectRepository->find($projectId))){
			$this->addFlash('danger','Erreur : projet non trouvé');
			return $this->redirect($referer);
		}

		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());
		if(!$me->canAdmin($project)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$project->setArchived(!$project->isArchived());
		$em->flush();
		if($project->isArchived()){
			$this->addFlash('success','Projet archivé');
		}else{
			$this->addFlash('success','Projet désarchivé');
		}
		return $this->redirect($referer);
	}

	/**
	 * @Route("/m/{projectId}/{way}", name="project_movelink", defaults={"projectId"=0},requirements={"projectId"="\d+"})
	 */
	public function moveLink(Request $request,$projectId,$way){
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$statusRepository = $this->getDoctrine()->getRepository(ProjectStatus::class);

		$referer = $request->headers->get('referer');

		if(!($project = $projectRepository->find($projectId))){
			$this->addFlash('danger','Erreur : projet non trouvé');
			return $this->redirect($referer);
		}

		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());
		if(!$me->canAdmin($project)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		if($way == "inc"){
			if($project->getProjectStatus() == NULL){
				$nextOrder = 1;
			}else{
				$nextOrder = $project->getProjectStatus()->getStatusOrder()+1;
			}
			$project->setProjectStatus($statusRepository->findInTeamByOrder($project->getTeam(),$nextOrder));
		}elseif($way == "dec"){
			if($project->getProjectStatus() != NULL){
				if($project->getProjectStatus()->getStatusOrder() == 1){
					$project->setProjectStatus(NULL);
				}else{
					$project->setProjectStatus($project->getProjectStatus()->getStatusOrder()-1);
				}
			}
		}
		$em->flush();

		return $this->redirect($referer);
	}

	/**
	 * @Route("/move/{projectId}/{newStatus}",name="project_move")
	 */
	public function move(Request $request,$projectId,$newStatus){
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$statusRepository = $this->getDoctrine()->getRepository(ProjectStatus::class);

		if(!($project = $projectRepository->find($projectId))){
			$arrData = ['success' => false, 'errormsg' => 'Projet non trouvé'];
			return new JsonResponse($arrData);
		}

		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());
		if(!$me->canAdmin($project)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		if($newStatus == 0){
			$project->setProjectStatus(NULL);
		}else{

			$status = $statusRepository->find($newStatus);
			if($status == NULL || $status->getTeam() != $project->getTeam()){
				$arrData = ['success' => false, 'errormsg' => 'Statut non trouvé'];
				return new JsonResponse($arrData);
			}
			$project->setProjectStatus($status);
		}

		$em->flush();
		$arrData = ['success' => true];

		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/del/{projectId}",name="project_del")
	 */
	public function del(Request $request,$projectId){
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if(!($project = $projectRepository->find($projectId))){
			$this->addFlash('danger','Projet inexistant');
		}else{
			$userRepository = $this->getDoctrine()->getRepository(User::class);
			$me = $userRepository->find($this->get('session')->get('user')->getId());
			if(!$me->canAdmin($project)){
				throw $this->createNotFoundException("Cette page n'existe pas");
			}

			if($project->isArchived()){
				$em->remove($project);
				$em->flush();
				$this->addFlash('success','Projet supprimé');
			}else{
				$this->addFlash('warning','Vous devez archiver un projet avant de le supprimer');
			}
		}
		$referer = $request->headers->get('referer');
		return $this->redirect($referer);
	}

}
