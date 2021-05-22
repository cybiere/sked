<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\ProjectStatus;
use App\Form\TeamType;
use App\Form\ProjectStatusType;
use App\Form\ProjectType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/team")
 */
class TeamController extends Controller
{
    /**
	 * @Route("/", name="team_index")
     */
	public function index(Request $request)
	{
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$team = new Team();

		$form = $this->createForm(TeamType::class,$team);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$em->persist($team);
			$em->flush();
			$this->addFlash('success','Équipe enregistrée');
			return $this->redirectToRoute('team_index');
		}

		$teams = $teamRepository->findAll();
		return $this->render('team/index.html.twig', [
			"form"=>$form->createView(),
			"teams"=>$teams
        ]);
	}

	/**
	 * @Route("/view/{teamId}",name="team_view",defaults={"teamId"="0"})
	 */
	public function view(Request $request,$teamId){
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if($teamId == 0){
			$teamId = $me->getTeam()->getId();
		}else{
			$allowedTeams = [];
			$myTeam = $me->getTeam();
			if($myTeam != null) $allowedTeams[] = $myTeam->getId();
			foreach($me->getManagedTeams() as $team){
				$allowedTeams[] = $team->getId();
			}
			if(!$this->get('session')->get('user')->isAdmin() and !in_array($teamId,$allowedTeams)){
				throw $this->createNotFoundException("Cette page n'existe pas");
			}
		}

		if(!($team = $teamRepository->find($teamId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$project = new Project();
		$projectForm = $this->createForm(ProjectType::class,$project,['teams'=>[$team],'users'=>$team->getUsers()]);

		$projectForm->handleRequest($request);
		if ($projectForm->isSubmitted() && $projectForm->isValid()) {
			$project = $projectForm->getData();
			if(!$me->canAdmin($project)){
				throw $this->createNotFoundException("Cette page n'existe pas");
			}
			$em->persist($project);
			$em->flush();
			$this->addFlash('success','Projet ajouté');
		}


		$projects = $projectRepository->findAll();

		$managedProjects = [];
		$managedUsers = [];
		if($me->isAdmin()){
			$managedProjects = $projects;
			$managedUsers = $userRepository->findBy(array("isResource"=>true));
		}else{
			foreach($projects as $project){
				if($me->canAdmin($project)){
					$managedProjects[] = $project;
				}
			}
			foreach($team->getUsers() as $user){
				if($me->canAdmin($user)){
					$managedUsers[] = $user;
				}
			}
		}

		try {
			$startDateObj = new \DateTime($startDate);
		} catch (\Exception $e) {
			$startDate = "now";
			$startDateObj = new \DateTime("now");
		}
		$renderMonths = 3;

		$maxOffsets = [];
		for($i=0;$i<$renderMonths;$i++){
			$offDate = clone $startDateObj;
			$offDate->modify("+".$i."months");
			foreach($team->getUsers() as $user){
				$maxOffsets[$i][$user->getId()] = CommonController::calcOffset($offDate,$user);
			}
		}

		return $this->render('team/view.html.twig', [
			'nbMonths' => 3,
			'maxOffsets' => $maxOffsets,
			"team"=>$team,
			"teams" => $teamRepository->findAll(),
			'holidays' => CommonController::getHolidays($startDateObj->format('Y')),
			'startDate' => $startDate,
			'users' => $team->getUsers(),
			'projects' => $projects,
			'projectForm' => $projectForm->createView(),
			'me' => $me,
        ]);
	}

	/**
	 * @Route("/del/{teamId}",name="team_del")
	 */
	public function del(Request $request,$teamId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);

		if(!($team = $teamRepository->find($teamId))){
			$this->addFlash('danger','Erreur : équipe non trouvée');
		}else{
			$em->remove($team);
			$em->flush();
		}
		$referer = $request->headers->get('referer');
		return $this->redirect($referer);
	}

	/**
	 * @Route("/edit/{teamId}",name="team_edit")
	 */
	public function edit(Request $request,$teamId){
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$projectStatusRepository = $this->getDoctrine()->getRepository(projectStatus::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($team = $teamRepository->find($teamId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

	if(!$this->get('session')->get('user')->isAdmin()){
			if(!$me->canAdmin($team))
				throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$projectStatus = new ProjectStatus();
		$form = $this->createForm(ProjectStatusType::class,$projectStatus);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$projectStatus->setTeam($team);
			$projectStatus->setStatusOrder($projectStatusRepository->findMaxOrder($team)+1);
			$em->persist($projectStatus);
			$em->flush();
			$this->addFlash('success','Statut enregistré');

			$projectStatus = new ProjectStatus();
			$form = $this->createForm(ProjectStatusType::class,$projectStatus);
		}

		return $this->render('team/edit.html.twig', [
			"team"=>$team,
			"users"=>$userRepository->findAll(),
			"form"=>$form->createView(),
			"statuses" => $projectStatusRepository->findByTeam($team)
        ]);
	}

	/**
	 * @Route("/addManager/{teamId}/{userId}",name="team_addManager")
	 */
	public function addManager(Request $request,$teamId,$userId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		if(!($team = $teamRepository->find($teamId))){
			$arrData = ['success' => false, 'errormsg' => 'Équipe non trouvée'];
		}elseif(!($user = $userRepository->find($userId))){
			$arrData = ['success' => false, 'errormsg' => 'Utilisateur non trouvé'];
		}else{
			$team->addManager($user);
			$em->flush();
			$arrData = ['success' => true];
		}
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/delManager/{teamId}/{userId}",name="team_delManager")
	 */
	public function delManager(Request $request,$teamId,$userId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		if(!($team = $teamRepository->find($teamId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}elseif(!($user = $userRepository->find($userId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}else{
			$team->removeManager($user);
			$em->flush();
		}
		$referer = $request->headers->get('referer');
		return $this->redirect($referer);
	}

	/**
	 * @Route("/orderStatus/{statusId}/{way}",name="team_changeStatusOrder")
	 */
	public function changeStatusOrder(Request $request,$statusId,$way){
		$em = $this->getDoctrine()->getManager();
		$statusRepository = $this->getDoctrine()->getRepository(ProjectStatus::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($status = $statusRepository->find($statusId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}elseif(!$me->canAdmin($status->getTeam())){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		if($way == "dec"){
			$switchWith = $statusRepository->findInTeamByOrder($status->getTeam(),$status->getStatusOrder()+1);
			if($switchWith != NULL){
				$newOrder = $switchWith->getStatusOrder();
				$switchWith->setStatusOrder($status->getStatusOrder());
				$status->setStatusOrder($newOrder);
			}
		}else{
			if($status->getStatusOrder() > 1){
				$switchWith = $statusRepository->findInTeamByOrder($status->getTeam(),$status->getStatusOrder()-1);
				if($switchWith != NULL){
					$newOrder = $switchWith->getStatusOrder();
					$switchWith->setStatusOrder($status->getStatusOrder());
					$status->setStatusOrder($newOrder);
			}

			}
		}
		$em->flush();

		$referer = $request->headers->get('referer');
		return $this->redirect($referer);
	}

	/**
	 * @Route("/delStatus/{statusId}",name="team_delStatus")
	 */
	public function delStatus(Request $request, $statusId){
		$em = $this->getDoctrine()->getManager();
		$statusRepository = $this->getDoctrine()->getRepository(ProjectStatus::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($status = $statusRepository->find($statusId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}elseif(!$me->canAdmin($status->getTeam())){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$i=1;
		foreach($status->getProjects() as $project){
			$project->setProjectStatus(null);
		}
		while(($nextStatus = $statusRepository->findInTeamByOrder($status->getTeam(),$status->getStatusOrder()+$i)) != NULL){
			$nextStatus->setStatusOrder($nextStatus->getStatusOrder()-1);
			$i++;
		}

		$em->remove($status);
		$em->flush();

		$referer = $request->headers->get('referer');
		return $this->redirect($referer);
	}
}
