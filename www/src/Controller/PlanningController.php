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
	 * @Route("/", name="planning_index")
	 * @Route("/planning/{startDate}", name="planning_index_shift", defaults={"startDate"="now"})
	 */
	public function index(Request $request, $startDate="now")
	{
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());
		$teams = $teamRepository->findAll();


		if($this->get('session')->get('user')->isAdmin() || count($teams) == 0){
			$users = $userRepository->findBy(array("isResource"=>true));
		}else{
			$myTeams = array_unique(array_merge($me->getTeams()->toArray(),$me->getManagedTeams()));
			if(count($myTeams) ==0){
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

		return $this->render('planning/index.html.twig', [
			'nbMonths' => 3,
			'maxOffsets' => $maxOffsets,
			'holidays' => CommonController::getHolidays($startDateObj->format('Y')),
			'startDate' => $startDate,
			'users' => $users,
			'projects' => $projects,
			'me' => $me,
			'hasAdmin' => $hasAdmin,
		]);

	}

	/**
	 * @Route("/p/resize/{planningId}/{newSize}",name="planning_resize")
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
	 * @Route("/p/info/{planningId}",name="planning_info")
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
	 * @Route("/p/new",name="planning_new")
	 */
	public function newPlanning(Request $request){
		if(!$request->isMethod('POST')){
			$arrData = ['success' => false, 'errormsg' => 'Méthode invalide'];
			return new JsonResponse($arrData);
		}

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
		$planning->setStartHour($data['startHour'] == "pm"?"pm":"am");
		$planning->setNbSlices($data['nbSlices']);
		$planning->setMeeting($data['meeting'] == "true"?true:false);
		$planning->setConfirmed($data['confirmed'] == "false"?false:true);
		if(($task = $taskRepository->find($data['task']))){
			$planning->setTask($task);
		}

		$em->persist($planning);
		$em->flush();
		$arrData = ['success' => true,'id' => $planning->getId()];
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/p/move/{planningId}/{newStart}/{newHour}/{newUser}/{newSize}",name="planning_move")
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
		if($newHour != "pm") $newHour = "am";
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
	 * @Route("/p/del/{planningId}",name="planning_del")
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
	 * @Route("/p/confirm/{planningId}",name="planning_confirm")
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
	 * @Route("/p/meeting/{planningId}",name="planning_meeting")
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
}
