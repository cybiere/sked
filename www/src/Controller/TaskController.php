<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Team;
use App\Entity\Project;
use App\Entity\User;
use App\Form\TaskType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/task")
 */
class TaskController extends Controller
{
	/**
	 * @Route("/{taskId}", name="task_index", defaults={"taskId"=0},requirements={"taskId"="\d+"})
	 */
	public function index(Request $request, $taskId=0)
	{
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$taskRepository = $this->getDoctrine()->getRepository(Task::class);

		$options = array();

		if(($task = $taskRepository->find($taskId))){
			if($task->isClosed()){
				$this->addFlash('danger','Impossible de modifier une tâche clôturée');
				return $this->redirectToRoute('task_index');
			}

			$projectRepository = $this->getDoctrine()->getRepository(Project::class);
			$teamRepository = $this->getDoctrine()->getRepository(Team::class);

			$project = $projectRepository->find($task->getProject());
			$team = $teamRepository->find($project->getTeam());

			$options['project'] = $project;
			$options['users'] = $team->getUsers();
		}else{
			$task = new Task();
			$userRepository = $this->getDoctrine()->getRepository(User::class);
			$options['users'] = $userRepository->findAll();
		}

		$form = $this->createForm(TaskType::class,$task,$options);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$em->persist($task);
			$em->flush();
			$this->addFlash('success','Tâche enregistrée');
			return $this->redirectToRoute('task_index');
		}

		$tasks = $taskRepository->findAll();
		return $this->render('task/index.html.twig', [
			'form' => $form->createView(),
			'tasks'=>$tasks
		]);
	}

	/**
	 * @Route("/export/task/{projectId}", name="task_export")
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
			if (! $planning->getTask()) continue;

			$key = array_search(
				($planning->getStartDate())->format("Y-m-d") . " " . $planning->getStartHour(),
				$calendar
			);

			if (false === $key) continue; // out of calendar range

			if (isset($calendars[($planning->getTask())->getName()][$key])) {
				$calendars[($planning->getTask())->getName()][$key] .= " | " . ($planning->getUser())->getFullname();
			} else {
				$calendars[($planning->getTask())->getName()][$key] = ($planning->getUser())->getFullname();
			}

			if ($planning->getNbSlices() == 0.5) continue;

			for ($i = 1; $i < ($planning->getNbSlices() * 2); $i++) {
				if (! array_key_exists($key + $i, $calendar)) continue; // out of calendar range

				if (isset($calendars[($planning->getTask())->getName()][$key + $i])) {
					$calendars[($planning->getTask())->getName()][$key + $i] .= " | " . ($planning->getUser())->getFullname();
				} else {
					$calendars[($planning->getTask())->getName()][$key + $i] = ($planning->getUser())->getFullname();
				}
			}
		}

		$response = $this->render('task/export.csv.twig', [
			'calendar' => $calendar,
			'calendars' => $calendars
		]);

		$response->headers->set('Content-Type', 'text/csv');
		$response->headers->set('Content-Disposition', 'attachment; filename="task.csv"');

		return $response;
	}

	/**
	 * @Route("/toggleDone/{taskId}", name="task_toggleDone", defaults={"taskId"=0},requirements={"taskId"="\d+"})
	 */
	public function toggleDone(Request $request, $taskId=0){
		$referer = $request->headers->get('referer');
		$em = $this->getDoctrine()->getManager();
		$taskRepository = $this->getDoctrine()->getRepository(Task::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($task = $taskRepository->find($taskId))){
			$this->addFlash('danger','Erreur : tâche non trouvée');
			return $this->redirect($referer);
		}
		if(!$me->canAdmin($task) && $task->getAssignedTo()->getId() != $this->get('session')->get('user')->getId()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		if($task->isClosed()){
			$this->addFlash('danger','Erreur : impossible de modifier une tâche clôturée');
		}else{
			$task->setDone(!$task->isDone());
			$em->flush();
		}
		return $this->redirect($referer);
	}

	/**
	 * @Route("/toggleClosed/{taskId}", name="task_toggleClosed", defaults={"taskId"=0},requirements={"taskId"="\d+"})
	 */
	public function toggleClosed(Request $request, $taskId=0){
		$referer = $request->headers->get('referer');
		$em = $this->getDoctrine()->getManager();
		$taskRepository = $this->getDoctrine()->getRepository(Task::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($task = $taskRepository->find($taskId))){
			$this->addFlash('danger','Erreur : tâche non trouvée');
			return $this->redirect($referer);
		}
		if(!$me->canAdmin($task)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$task->setClosed(!$task->isClosed());
		$em->flush();
		return $this->redirect($referer);
	}

	/**
	 * @Route("/edit/{taskId}", name="task_edit", defaults={"taskId"=0},requirements={"taskId"="\d+"})
	 */
	public function edit(Request $request, $taskId=0){
		$referer = $request->headers->get('referer');
		$em = $this->getDoctrine()->getManager();
		$taskRepository = $this->getDoctrine()->getRepository(Task::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($task = $taskRepository->find($taskId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		if($task->isClosed()){
			$this->addFlash('danger','Impossible de modifier une tâche clôturée');
			return $this->redirect($referer);
		}

		if(!$me->canAdmin($task)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$managedUsers = $userRepository->findAll();
		if(!$me->isAdmin()){
			foreach($managedUsers as $key => $user){
				if(!$me->canAdmin($user)){
					unset($managedUsers[$key]);
				}
			}
		}

		$form = $this->createForm(TaskType::class,$task,["project"=>$task->getProject(),"users"=>$managedUsers]);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$em->persist($task);
			$em->flush();
			$this->addFlash('success','Tâche enregistrée');
			if($task->getProject())
				return $this->redirectToRoute('project_view',['projectId'=>$task->getProject()->getId()]);
			return $this->redirectToRoute('task_index');
		}

		return $this->render('task/edit.html.twig', [
			'form' => $form->createView()
		]);
	}

	/**
	 * @Route("/del/{taskId}", name="task_del", defaults={"taskId"=0},requirements={"taskId"="\d+"})
	 */
	public function del(Request $request, $taskId=0){
		$referer = $request->headers->get('referer');
		$em = $this->getDoctrine()->getManager();
		$taskRepository = $this->getDoctrine()->getRepository(Task::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($task = $taskRepository->find($taskId))){
			$this->addFlash('danger','Erreur : tâche non trouvée');
			return $this->redirect($referer);
		}

		if(!$me->canAdmin($task)){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		if(!$task->isClosed()){
			$this->addFlash('danger',"Une tâche doit être fermée avant d'être supprimée.");
		}else{
			$em->remove($task);
			$em->flush();
			$this->addFlash('success','Tâche supprimée');
		}
		return $this->redirect($referer);
	}

	/**
	 * @Route("/byproject/{projectId}",name="task_byproject")
	 */
	public function byProject(Request $request,$projectId){
		$em = $this->getDoctrine()->getManager();
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());

		if(!($project = $projectRepository->find($projectId))){
			$arrData = ['success' => false, 'errormsg' => 'Projet non trouvé'];
			return new JsonResponse($arrData);
		}
		
		if(!$me->canAdmin($project)){
			throw $this->createNotFoundException("Cette page n'existe pas");
			return new JsonResponse($arrData);
		}
		else{
			$taskRepository = $this->getDoctrine()->getRepository(Task::class);
			$tasks = $taskRepository->findByProject($project);
			$arrData = ['success' => true,'tasks'=> array()];
			foreach($tasks as $task){
				$arrData['tasks'][] = ['id' => $task->getId(), 'name' => $task->getName()];
			}
		}
		return new JsonResponse($arrData);
	}


}
