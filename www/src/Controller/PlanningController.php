<?php

namespace App\Controller;

use App\Entity\User;
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
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);

		$planning = new Planning();
		$form = $this->createForm(PlanningType::class,$planning);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			if(!$this->get('session')->get('user')->isAdmin()){
				throw $this->createNotFoundException("Cette page n'existe pas");
			}
			$planning = $form->getData();
			$em->persist($planning);
			$em->flush();
			$this->addFlash('success','Planning ajouté');
		}


		$users = $userRepository->findBy(array("isResource"=>true));
		$projects = $projectRepository->findAll();

		try {
			$startDateObj = new \DateTime($startDate);
		} catch (\Exception $e) {
			$startDate = "now";
			$startDateObj = new \DateTime("now");
	    }
		$baseYear = intval($startDateObj->format('Y'));
		$holidays = array();
		for($i=-1;$i<=1;$i++){
			$year=$baseYear+$i;
			$easterDate  = \easter_date($year);
			$easterDay   = date('j', $easterDate);
			$easterMonth = date('n', $easterDate);
			$easterYear  = date('Y', $easterDate);

				// Dates fixes
			$holidays[] = mktime(0, 0, 0, 1,  1,  $year);  // 1er janvier
			$holidays[] = mktime(0, 0, 0, 5,  1,  $year);  // Fête du travail
			$holidays[] = mktime(0, 0, 0, 5,  8,  $year);  // Victoire des alliés
			$holidays[] = mktime(0, 0, 0, 7,  14, $year);  // Fête nationale
			$holidays[] = mktime(0, 0, 0, 8,  15, $year);  // Assomption
			$holidays[] = mktime(0, 0, 0, 11, 1,  $year);  // Toussaint
			$holidays[] = mktime(0, 0, 0, 11, 11, $year);  // Armistice
			$holidays[] = mktime(0, 0, 0, 12, 25, $year);  // Noel

				// Dates variables
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear);
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear);
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear);
		}
		sort($holidays);

		return $this->render('planning/index.html.twig', [
			'holidays' => $holidays,
			'startDate' => $startDate,
			'users' => $users,
			'projects' => $projects,
			'form' => $form->createView(),
		]);

	}

	/**
	 * @Route("/resize/{planningId}/{newSize}",name="planning_resize")
	 */
	public function resize(Request $request,$planningId,$newSize){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
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
	 * @Route("/move/{planningId}/{newStart}/{newHour}/{newUser}/{newSize}",name="planning_move")
	 */
	public function move(Request $request,$planningId,$newStart,$newHour,$newUser,$newSize){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		if(!($planning = $planningRepository->find($planningId))){
			$arrData = ['success' => false, 'errormsg' => 'Projet non trouvé'];
		}elseif(!($user = $userRepository->find($newUser))){
			$arrData = ['success' => false, 'errormsg' => 'Utilisateur non trouvé'];
		}else{
			if($newHour != "pm") $newHour = "am";
			if($newSize < 1) $newSize = 1;
			$planning->setStartDate(new \DateTime($newStart));
			$planning->setStartHour($newHour);
			$planning->setNbSlices($newSize);
			$planning->setUser($user);
			$em->flush();
			$arrData = ['success' => true];
		}
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/del/{planningId}",name="planning_del")
	 */
	public function del(Request $request,$planningId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);

		if(!($planning = $planningRepository->find($planningId))){
			$this->addFlash('danger','Erreur : élément de planning non trouvé');
			return $this->redirectToRoute('planning_index');
		}

		$em->remove($planning);
		$em->flush();
		return $this->redirectToRoute('planning_index');
	}
}
