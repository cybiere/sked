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

			if ($request->isXmlHttpRequest())
				return new JsonResponse(
					array(
						'client' => $project->getClient(),
						'name' => $project->getName(),
						'id' => $project->getId(),
						'nbDays' => $project->getNbDays(),
						'plannedDays' => $project->getPlannedDays()
					)
				);
		}

		if($this->get('session')->get('user')->isAdmin()){
			$myTeams = $teamRepository->findAll();
		}else{
			if($me->getTeam() == null){
				$myTeams = $me->getManagedTeams();
			}elseif($me->getManagedTeams() == null){
				$myTeams = [$me->getTeam()];
			}else{
				$myTeams = $me->getManagedTeams();
				if(!in_array($me->getTeam(),$myTeams))
					$myTeams[] = $me->getTeam();
			}
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
				$form = $this->createForm(TaskType::class,$task,["project"=>$project,"users"=>$managedUsers]);
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
		if(count($plannings) < 1){
			$nbMonths = 1;
		}else{
			$endMonth = $plannings[count($plannings)-1]->getStartDate()->format('n');
			$startMonth = $startDateObj->format('n');
			$endYear = $plannings[count($plannings)-1]->getStartDate()->format('Y');
			$startYear = $startDateObj->format('Y');
			while($endYear > $startYear){
				$endYear--;
				$endMonth+=12;
			}
			$nbMonths = $endMonth - $startMonth +1;
		}

		$maxOffsets = [];
		$maxOffsets[-1][2] = 0;
		$maxOffsets[-1][3] = 0;
		$maxOffsets[-1][4] = 0;
		$maxOffsets[-1][5] = 0;
		for($i=0;$i<$nbMonths;$i++){
			$offDate = clone $startDateObj;
			$offDate->modify("+".$i."months");
			foreach($users as $user){
				$maxOffsets[$i][$user->getId()] = CommonController::calcOffset($offDate,$user);
			}
		}


		return $this->render('project/view.html.twig',array(
			'nbMonths' => $nbMonths,
			'maxOffsets' => $maxOffsets,
			'startDate' => $startDateObj,
			'project'=>$project,
			'plannings'=>$plannings,
			'users'=>$users,
			'holidays'=>CommonController::getHolidays($startDateObj->format('Y')),
			'form'=>$form->createView(),
			'me'=>$me));
	}


	/**
	 * @Route("/export/project/{projectId}", name="project_export")
	 */
	public function export(Request $request, $projectId)
	{
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		if(!($project = $projectRepository->find($projectId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$calendar = array();
		$period = array(
			'begin' => null,
			'end' => null
		);

		foreach ($project->getPlannings() as $planning) {
			// find largest period range
			if (
				! $period['begin'] ||
				$period['begin'] > $planning->getStartDate()
			) {
				$period['begin'] = $planning->getStartDate();
			}
		}

		$renderMonths = 3;

		// default calendar and users value
		$calendar = array();
		$calendars = array();

		// iterate over period
		$daterange = new \DatePeriod($period['begin'], new \DateInterval('P1D'), new \DateTime("+ {$renderMonths}months"));
		foreach ($daterange as $date) {
			if (in_array($date->format("Y-m-d"), CommonController::getHolidays($period['begin']->format('Y'), "Y-m-d"))) continue;
			if ($date->format("N") > 5) continue;

			$calendar[] = $date->format("Y-m-d") . " am";
			$calendar[] = $date->format("Y-m-d") . " am2";
			$calendar[] = $date->format("Y-m-d") . " pm";
			$calendar[] = $date->format("Y-m-d") . " pm2";
		}

		// look for user planning event by period
		foreach ($project->getPlannings() as $planning) {
			$key = array_search(
				($planning->getStartDate())->format("Y-m-d") . " " . $planning->getStartHour(),
				$calendar
			);

			if (false === $key) continue; // out of calendar range

			if (isset($calendars[$project->getName()][$key])) {
				$calendars[$project->getName()][$key] .= " | " . ($planning->getUser())->getFullname();
			} else {
				$calendars[$project->getName()][$key] = ($planning->getUser())->getFullname();
			}

			if ($planning->getNbSlices() == 0.5) continue;

			for ($i = 1; $i < ($planning->getNbSlices() * 2); $i++) {
				if (! array_key_exists($key + $i, $calendar)) continue; // out of calendar range

				if (isset($calendars[$project->getName()][$key + $i])) {
					$calendars[$project->getName()][$key + $i] .= " | " . ($planning->getUser())->getFullname();
				} else {
					$calendars[$project->getName()][$key + $i] = ($planning->getUser())->getFullname();
				}
			}
		}

		$response = $this->render('project/export.csv.twig', [
			'calendar' => $calendar,
			'calendars' => $calendars
		]);

		$response->headers->set('Content-Type', 'text/csv');
		$response->headers->set('Content-Disposition', 'attachment; filename="project.csv"');

		return $response;
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

	/**
	 * @Route("/projects/getUsers/{id}",name="project_getUsers")
	 */
	public function getUsers(Request $request, $id){
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if (! ($project = $projectRepository->find($id))) {
			throw $this->createNotFoundException("Ce projet n'existe pas");
		}

		if (! $me->canAdmin($project)) {
			throw $this->createNotFoundException("Cette page n'existe pas");
		} else {
			$teamRepository = $this->getDoctrine()->getRepository(Team::class);
			$team = $teamRepository->find($project->getTeam());
			$arrData = [ 'success' => true, 'users '=> array() ];
			foreach ($team->getUsers() as $user) {
				$arrData['users'][] = [ 'id' => $user->getId(), 'name' => $user->getFullname() ];
			}
		}

		return new JsonResponse($arrData);
	}

}
