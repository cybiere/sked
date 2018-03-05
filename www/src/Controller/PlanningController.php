<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Planning;
use App\Form\PlanningType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PlanningController extends Controller
{
    /**
     * @Route("/", name="planning_index")
     */
    public function index(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		$planning = new Planning();
		$form = $this->createForm(PlanningType::class,$planning);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$planning = $form->getData();
			$em->persist($planning);
			$em->flush();
		 	$this->addFlash('success','Planning ajouté');
		}


		$users = $userRepository->findAll();

        return $this->render('planning/index.html.twig', [
			'users' => $users,
			'form' => $form->createView(),
        ]);
    }
}
