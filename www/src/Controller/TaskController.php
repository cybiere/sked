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
}
