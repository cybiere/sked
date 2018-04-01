<?php

namespace App\Controller;

use App\Entity\User;
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

	/**
	 * @Route("/resize/{planningId}/{newSize}",name="planning_resize")
	 */
	public function resize(Request $request,$planningId,$newSize){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Elément de planning non trouvé'];
		}else{
			if($newSize < 1) $newSize = 1;
			$planning->setNbSlices($newSize);
			$em->flush();
			$arrData = ['success' => true];
		}
        return new JsonResponse($arrData);
	}

	/**
	 * @Route("/move/{planningId}/{newStart}/{newHour}/{newUser}",name="planning_move")
	 */
	public function move(Request $request,$planningId,$newStart,$newHour,$newUser){
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Projet non trouvé'];
		}elseif(!($user = $userRepository->find($newUser))){
			$arrData = ['success' => false, 'errormsg' => 'Utilisateur non trouvé'];
		}else{
			if($newHour != "pm") $newHour = "am";
			$planning->setStartDate(new \DateTime($newStart));
			$planning->setStartHour($newHour);
			$planning->setUser($user);
			$em->flush();
			$arrData = ['success' => true];
		}
        return new JsonResponse($arrData);
	}
}
