<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Project;
use App\Entity\User;
use App\Form\TaskType;

use Symfony\Component\HttpFoundation\Request;
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

		if(!($task = $taskRepository->find($taskId)))
			$task = new Task();

		$form = $this->createForm(TaskType::class,$task);
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
	 * @Route("/toggleDone/{taskId}", name="task_toggleDone", defaults={"taskId"=0},requirements={"taskId"="\d+"})
	 */
	public function toggleDone(Request $request, $taskId=0){
		$referer = $request->headers->get('referer');
		$em = $this->getDoctrine()->getManager();
		$taskRepository = $this->getDoctrine()->getRepository(Task::class);

		if(!($task = $taskRepository->find($taskId))){
			$this->addFlash('danger','Erreur : tâche non trouvée');
			return $this->redirect($referer);
		}
		if(!$this->get('session')->get('user')->isAdmin() && $task->getAssignedTo()->getId() != $this->get('session')->get('user')->getId() && ($task->getProject() != NULL && $task->getProject()->getProjectManager()->getId() != $this->get('session')->get('user')->getId())){
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

		if(!($task = $taskRepository->find($taskId))){
			$this->addFlash('danger','Erreur : tâche non trouvée');
			return $this->redirect($referer);
		}
		if(!$this->get('session')->get('user')->isAdmin() && ($task->getProject() == NULL || $task->getProject()->getProjectManager()->getId() != $this->get('session')->get('user')->getId())){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$task->setClosed(!$task->isClosed());
		$em->flush();
		return $this->redirect($referer);
	}

	/**
	 * @Route("/edit/{taskId}", name="task_edit", defaults={"taskId"=0},requirements={"taskId"="\d+"})
	 */
	public function edit(Request $request, $taskId=0)
	{
		$em = $this->getDoctrine()->getManager();
		$taskRepository = $this->getDoctrine()->getRepository(Task::class);

		if(!($task = $taskRepository->find($taskId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		if(!$this->get('session')->get('user')->isAdmin() && !($task->getProject() != null && $task->getProject()->getProjectManager() != null && $task->getProject()->getProjectManager()->getId() == $this->get('session')->get('user')->getId())){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$form = $this->createForm(TaskType::class,$task,["project"=>$task->getProject()]);
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
}
