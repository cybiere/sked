<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Team;
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

		$planning = new Planning();
		$form = $this->createForm(PlanningType::class,$planning,
			[
				'projects'=>$managedProjects,
				'users'=>$managedUsers
			]);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$planning = $form->getData();
			if(!$me->canAdmin($planning)){
				$this->addFlash('danger',"Vous n'avez pas le droit de modifier ce planning.");
			}else{
				if(!is_object($planning) && $planning->getProject() == 0) $planning->setProject(NULL);
				$em->persist($planning);
				$em->flush();
				$this->addFlash('success','Planning ajouté');
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
		return $this->render('planning/index.html.twig', [
			'holidays' => CommonController::getHolidays($startDateObj->format('Y')),
			'startDate' => $startDate,
			'users' => $users,
			'projects' => $projects,
			'form' => $form->createView(),
			'me' => $me,
			'hasAdmin' => $hasAdmin,
		]);

	}

	/**
	 * @Route("/resize/{planningId}/{newSize}",name="planning_resize")
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
	 * @Route("/move/{planningId}/{newStart}/{newHour}/{newUser}/{newSize}",name="planning_move")
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
	 * @Route("/del/{planningId}",name="planning_del")
	 */
	public function del(Request $request,$planningId){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		$referer = $request->headers->get('referer');
		if(!($planning = $planningRepository->find($planningId))){
			$this->addFlash('danger','Erreur : élément de planning non trouvé');
			return $this->redirect($referer);
		}

		if(!$me->canAdmin($planning)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em->remove($planning);
		$em->flush();
		return $this->redirect($referer);
	}

	/**
	 * @Route("/confirm/{planningId}",name="planning_confirm")
	 */
	public function confirm(Request $request,$planningId){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		$referer = $request->headers->get('referer');
		if(!($planning = $planningRepository->find($planningId))){
			$this->addFlash('danger','Erreur : élément de planning non trouvé');
			return $this->redirect($referer);
		}
		if(!$me->canAdmin($planning)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$planning->setConfirmed($planning->isConfirmed()?false:true);
		$em->flush();


		return $this->redirect($referer);
	}

	/**
	 * @Route("/meeting/{planningId}",name="planning_meeting")
	 */
	public function meeting(Request $request,$planningId){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		$referer = $request->headers->get('referer');
		if(!($planning = $planningRepository->find($planningId))){
			$this->addFlash('danger','Erreur : élément de planning non trouvé');
			return $this->redirect($referer);
		}
		if(!$me->canAdmin($planning)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$planning->setMeeting($planning->isMeeting()?false:true);
		$em->flush();


		return $this->redirect($referer);
	}


}
