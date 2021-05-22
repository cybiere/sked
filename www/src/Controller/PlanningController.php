<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Team;
use App\Entity\Task;
use App\Entity\Project;
use App\Entity\Planning;
use App\Form\PlanningType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PlanningController extends Controller
{
	/**
	 * @Route("/planning", name="planning_index")
	 * @Route("/planning/{startDate}", name="planning_index_shift", defaults={"startDate"="now"}, methods={"GET"})
	 */
	public function index(Request $request, $startDate="now")
	{
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());
		$teams = $teamRepository->findAll();


		if($this->get('session')->get('user')->isAdmin() || count($teams) == 0){
			$users = $userRepository->findBy(array("isResource"=>true));
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
			if(count($myTeams)==0){
				$users = [$me];
			}else{
				$users = [];
				foreach($myTeams as $team){
					$users = array_merge($users,$team->getUsers()->toArray());
				}
				$users = array_unique($users);
				foreach($users as $key=>$user){
					if(!$user->isResource()){
						unset($users[$key]);
					}
				}
			}
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
			foreach($users as $user){
				if($me->canAdmin($user)){
					$managedUsers[] = $user;
				}
			}
		}

		$hasAdmin = $me->isAdmin();
		foreach($users as $user){
			if($hasAdmin) break;
			$hasAdmin = $user != $me && $me->canAdmin($user);
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
			foreach($users as $user){
				$maxOffsets[$i][$user->getId()] = CommonController::calcOffset($offDate,$user);
			}
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
		}

		return $this->render('planning/index.html.twig', [
			'nbMonths' => 3,
			'maxOffsets' => $maxOffsets,
			'holidays' => CommonController::getHolidays($startDateObj->format('Y')),
			'startDate' => $startDate,
			'users' => $users,
			'teams' => $myTeams,
			'projects' => $projects,
			'me' => $me,
			'hasAdmin' => $hasAdmin,
		]);

	}

	/**
	 * @Route("/export/planning", name="planning_export")
	 * @Route("/export/planning/{startDate}", name="planning_export_shift", defaults={"startDate"="now"}, methods={"GET"})
	 */
	public function export(Request $request, $startDate="now")
	{
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$userRepository = $this->getDoctrine()->getRepository(User::class);

		$users = $userRepository->findBy(array("isResource"=>true));

		try {
			$startDateObj = new \DateTime($startDate);
		} catch (\Exception $e) {
			$startDate = "now";
			$startDateObj = new \DateTime("now");
		}

		$renderMonths = 3;

		// default calendar and users value
		$calendar = array();
		$calendars = array();

		// iterate over period
		$daterange = new \DatePeriod($startDateObj, new \DateInterval('P1D'), new \DateTime("+ {$renderMonths}months"));
		foreach ($daterange as $date) {
			if (in_array($date->format("Y-m-d"), CommonController::getHolidays($startDateObj->format('Y'), "Y-m-d"))) continue;
			if ($date->format("N") > 5) continue;

			$calendar[] = $date->format("Y-m-d") . " am";
			$calendar[] = $date->format("Y-m-d") . " am2";
			$calendar[] = $date->format("Y-m-d") . " pm";
			$calendar[] = $date->format("Y-m-d") . " pm2";
		}

		// look for user planning event by period
		foreach ($users as $user) {
			$calendars[$user->getFullname()] = array();

			foreach ($user->getPlannings() as $planning) {
				$key = array_search(
					($planning->getStartDate())->format("Y-m-d") . " " . $planning->getStartHour(),
					$calendar
				);
	
				if (! $key) continue; // out of calendar range

				if (! $planning->getProject()) {
					$value = "absence";
				} else {
					$value = ($planning->getProject())->getReference();
					if ($planning->getTask()) {
						$value .= " - " . ($planning->getTask())->getName();
					}
				}

				$calendars[$user->getFullname()][$key] = $value;

				if ($planning->getNbSlices() == 0.5) continue;

				for ($i = 1; $i < ($planning->getNbSlices() * 2); $i++) {
					if (! array_key_exists($key + $i, $calendar)) continue; // out of calendar range

					$calendars[$user->getFullname()][$key + $i] = $value;
				}
			}
		}

		$response = $this->render('planning/export.csv.twig', [
			'calendar' => $calendar,
			'calendars' => $calendars
		]);

		$response->headers->set('Content-Type', 'text/csv');
		$response->headers->set('Content-Disposition', 'attachment; filename="planning.csv"');

		return $response;
	}

	/**
	 * @Route("/planning/resize/{planningId}/{newSize}",name="planning_resize")
	 */
	public function resize(Request $request,$planningId,$newSize){
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Elément de planning non trouvé'];
			return new JsonResponse($arrData);
		}
		if(!$me->canAdmin($planning)){
			$arrData = ['success' => false, 'errormsg' => "Vous n'avez pas le droit de modifier ce planning"];
			return new JsonResponse($arrData);
		}
		$em = $this->getDoctrine()->getManager();
		if($newSize < 1) $newSize = 1;
		$planning->setNbSlices($newSize);
		$em->flush();
		$arrData = ['success' => true];
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/planning/info/{planningId}",name="planning_info")
	 */
	public function info(Request $request,$planningId){
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Elément de planning non trouvé'];
			return new JsonResponse($arrData);
		}
		$project = $planning->getProject();
		$task = $planning->getTask();
		$arrData = [
			'success' => true,
			'planningId'=> $planning->getId(),
			'duration' => $planning->getNbSlices(),
			'confirmed' => $planning->isConfirmed(),
			'meeting' => $planning->isMeeting(),
			'meetup' => $planning->isMeetup(),
			'deliverable' => $planning->isDeliverable(),
			'capitalization' => $planning->isCapitalization(),
			'monitoring' => $planning->isMonitoring(),
			'admin'=> $me->canAdmin($planning),
		];
		if($project != NULL){
			$arrData['projectId'] = $project->getId();
			$arrData['projectName'] = $project->getName();
			$arrData['projectReference'] = $project->getReference();
			$arrData['projectClient'] = $project->getClient();
			$arrData['projectPlannedDays'] = $project->getPlannedDays();
			$arrData['projectNbDays'] = $project->getNbDays();
			$arrData['projectComments'] = $project->getComments();
			$arrData['projectBillable'] = $project->isBillable();
			if($project->getProjectManager() != NULL){
				$arrData['projectManagerId'] = $project->getProjectManager()->getId();
				$arrData['projectManagerName'] = $project->getProjectManager()->getFullname();
			}
		}else{
			$arrData['projectId'] = 0;
			$arrData['projectName'] = "Absence";
		}
		if($task != NULL){
			$arrData['taskId'] = $task->getId();
			$arrData['taskName'] = $task->getName();
			$arrData['taskComments'] = $task->getComments();
		}else{
			$arrData['taskId'] = 0;
		}	

		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/planning/new", name="planning_new", methods={"POST"})
	 */
	public function newPlanning(Request $request){
		$data = $request->request->all();
		$em = $this->getDoctrine()->getManager();

		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$taskRepository = $this->getDoctrine()->getRepository(Task::class);

		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if($data['project'] != 0){
			if(!($project = $projectRepository->find($data['project']))){
				$arrData = ['success' => false, 'errormsg' => 'Impossible de trouver le projet associé'];
				return new JsonResponse($arrData);
			}
		}else{
			$project = NULL;
		}
		if(!($user = $userRepository->find($data['user']))){
			$arrData = ['success' => false, 'errormsg' => 'Impossible de trouver la ressource associée'];
			return new JsonResponse($arrData);
		}

		if(!$me->canAdmin($user) || ($project != NULL && !$me->canAdmin($project))){
			$arrData = ['success' => false, 'errormsg' => "Vous n'avez pas le droit de créer ce planning"];
			return new JsonResponse($arrData);
		}

		$planning = new Planning();
		$planning->setUser($user);
		$planning->setProject($project);
		$planning->setStartDate(new \DateTime($data['startDate']));
		if (! in_array(
			$data['startHour'],
			array(
				'am',
				'am2',
				'pm',
				'pm2'
			)
		)) {
			$planning->setStartHour("am");
		} else {
			$planning->setStartHour($data['startHour']);
		}

		$planning->setNbSlices($data['nbSlices']);
		$planning->setMeeting($data['meeting'] == "true"?true:false);
		$planning->setConfirmed($data['confirmed'] == "false"?false:true);
		$planning->setMeetup($data['meetup'] == "false"?false:true);
		$planning->setDeliverable($data['deliverable'] == "false"?false:true);
		$planning->setCapitalization($data['capitalization'] == "false"?false:true);
		$planning->setMonitoring($data['monitoring'] == "false"?false:true);

		if(($task = $taskRepository->find($data['task']))){
			$planning->setTask($task);
		}
		$planning->setComments($data['comments']);

		$em->persist($planning);
		$em->flush();
		$arrData = ['success' => true,'id' => $planning->getId()];
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/planning/move/{planningId}/{newStart}/{newHour}/{newUser}/{newSize}",name="planning_move")
	 */
	public function move(Request $request,$planningId,$newStart,$newHour,$newUser,$newSize){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Projet non trouvé'];
			return new JsonResponse($arrData);
		}elseif(!($user = $userRepository->find($newUser))){
			$arrData = ['success' => false, 'errormsg' => 'Utilisateur non trouvé'];
			return new JsonResponse($arrData);
		}

		if(!$me->canAdmin($planning)){
			$arrData = ['success' => false, 'errormsg' => "Vous n'avez pas le droit de modifier ce planning"];
			return new JsonResponse($arrData);
		}

		if (! in_array(
			$newHour,
			array(
				'am',
				'am2',
				'pm',
				'pm2'
			)
		)) {
			$newHour = "am";
		}

		if($newSize < 1) $newSize = 1;
		$planning->setStartDate(new \DateTime($newStart));
		$planning->setStartHour($newHour);
		$planning->setNbSlices($newSize);
		$planning->setUser($user);
		$em->flush();
		$arrData = ['success' => true];
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/planning/del/{planningId}",name="planning_del")
	 */
	public function del(Request $request,$planningId){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Planning non trouvé'];
			return new JsonResponse($arrData);
		}

		if(!$me->canAdmin($planning)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em->remove($planning);
		$em->flush();
		$arrData = ['success' => true];
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/planning/confirm/{planningId}",name="planning_confirm")
	 */
	public function confirm(Request $request,$planningId){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Planning non trouvé'];
			return new JsonResponse($arrData);
		}
		if(!$me->canAdmin($planning)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$planning->setConfirmed($planning->isConfirmed()?false:true);
		$em->flush();
		if($planning->isMeeting()){
			if($planning->isConfirmed()){
				$addclass="meeting";
			}else{
				$addclass="meeting-unconfirmed";
			}
		}else{
			if($planning->isConfirmed()){
				$addclass="billable";
			}else{
				$addclass="billable-unconfirmed";
			}
		}
		$arrData = ['success' => true,'addclass' => $addclass];
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/planning/meeting/{planningId}",name="planning_meeting")
	 */
	public function meeting(Request $request,$planningId){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Planning non trouvé'];
			return new JsonResponse($arrData);
		}
		if(!$me->canAdmin($planning)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$planning->setMeeting($planning->isMeeting()?false:true);
		$em->flush();

		if($planning->isMeeting()){
			if($planning->isConfirmed()){
				$addclass="meeting";
			}else{
				$addclass="meeting-unconfirmed";
			}
		}else{
			if($planning->isConfirmed()){
				$addclass="billable";
			}else{
				$addclass="billable-unconfirmed";
			}
		}
		$arrData = ['success' => true,'addclass' => $addclass];
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/planning/deliverable/{planningId}",name="planning_deliverable")
	 */
	public function deliverable(Request $request,$planningId){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Planning non trouvé'];
			return new JsonResponse($arrData);
		}
		if(!$me->canAdmin($planning)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$planning->setDeliverable($planning->isDeliverable()?false:true);
		$em->flush();

		if($planning->isDeliverable()){
			if($planning->isConfirmed()){
				$addclass="deliverable";
			}else{
				$addclass="deliverable-unconfirmed";
			}
		}else{
			if($planning->isConfirmed()){
				$addclass="billable";
			}else{
				$addclass="billable-unconfirmed";
			}
		}
		$arrData = ['success' => true,'addclass' => $addclass];
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/planning/meetup/{planningId}",name="planning_meetup")
	 */
	public function meetup(Request $request,$planningId){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Planning non trouvé'];
			return new JsonResponse($arrData);
		}
		if(!$me->canAdmin($planning)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$planning->setMeetup($planning->isMeetup()?false:true);
		$em->flush();

		if($planning->isMeetup()){
			if($planning->isConfirmed()){
				$addclass="meetup";
			}else{
				$addclass="meetup-unconfirmed";
			}
		}else{
			if($planning->isConfirmed()){
				$addclass="billable";
			}else{
				$addclass="billable-unconfirmed";
			}
		}
		$arrData = ['success' => true,'addclass' => $addclass];
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/planning/capitalization/{planningId}",name="planning_capitalization")
	 */
	public function capitalization(Request $request,$planningId){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Planning non trouvé'];
			return new JsonResponse($arrData);
		}
		if(!$me->canAdmin($planning)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$planning->setCapitalization($planning->isCapitalization()?false:true);
		$em->flush();

		if($planning->isCapitalization()){
			if($planning->isConfirmed()){
				$addclass="capitalization";
			}else{
				$addclass="capitalization-unconfirmed";
			}
		}else{
			if($planning->isConfirmed()){
				$addclass="billable";
			}else{
				$addclass="billable-unconfirmed";
			}
		}
		$arrData = ['success' => true,'addclass' => $addclass];
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/planning/coment/{planningId}",name="planning_comment", methods={"POST"})
	 */
	public function planning_comment(Request $request, $planningId) {

		$data = $request->request->all();
		$em = $this->getDoctrine()->getManager();

		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);

		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if (!$planning = $planningRepository->find($planningId)){
			$arrData = ['success' => false, 'errormsg' => "Impossible de trouver ce planning"];
			return new JsonResponse($arrData);
		}

		if(!$me->canAdmin($planning)){
			$arrData = ['success' => false, 'errormsg' => "Vous n'avez pas le droit de créer ce planning"];
			return new JsonResponse($arrData);
		}

		$planning->setComments($data['comments']);

		$em->persist($planning);
		$em->flush();

		$arrData = ['success' => true,'id' => $planning->getId()];
		return new JsonResponse($arrData);
		}
}
