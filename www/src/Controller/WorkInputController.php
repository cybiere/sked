<?php

namespace App\Controller;

use App\Entity\WorkInput;
use App\Form\WorkInputType;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Planning;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/report")
 */
class WorkInputController extends Controller
{
    /**
     * @Route("/", name="report_index")
	 * @Route("/w/{startDate}", name="report_index_shift", defaults={"startDate"="now"})
     */
    public function index(Request $request, $startDate="now")
    {
		$em = $this->getDoctrine()->getManager();
		$inputRepository = $this->getDoctrine()->getRepository(WorkInput::class);

		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$projects = $projectRepository->findAll();

		//get last monday
		try{
			$startDate=date('Y-m-d', strtotime('previous monday', strtotime('tomorrow', strtotime($startDate))));
		} catch (\Exception $e) {
			$startDate=date('Y-m-d', strtotime('previous monday', strtotime('tomorrow')));
		}
		$startDateObj = new \DateTime($startDate);

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

		$myInputs = $inputRepository->findBy([
			'user'=>$this->get('session')->get('user')->getId(),
			'weekStart'=>$startDateObj
		]);

		$dailySum = array();
		for($i=0;$i<5;$i++){
			$dailySum[$i] = 0;
			foreach($myInputs as $input){
				$dailySum[$i] += $input->getDay($i);
			}
		}

		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);
		$userId = $this->get('session')->get('user')->getId();

		$plannings = array();
		$plannings['mon'] = $planningRepository->findActiveByDay($startDateObj,$userId);
		$startDateObj->add(new \DateInterval('P1D'));
		$plannings['tue'] = $planningRepository->findActiveByDay($startDateObj,$userId);
		$startDateObj->add(new \DateInterval('P1D'));
		$plannings['wed'] = $planningRepository->findActiveByDay($startDateObj,$userId);
		$startDateObj->add(new \DateInterval('P1D'));
		$plannings['thu'] = $planningRepository->findActiveByDay($startDateObj,$userId);
		$startDateObj->add(new \DateInterval('P1D'));
		$plannings['fri'] = $planningRepository->findActiveByDay($startDateObj,$userId);

		return $this->render('work_input/index.html.twig', [
			'holidays' => $holidays,
			'plannings' => $plannings,
			'inputs'=>$myInputs,
			'projects'=>$projects,
			'startDate'=>$startDate,
			'dailySum'=>$dailySum
		]);
	}

	/**
	 * @Route("/add", name="report_add")
	 */
	public function add(Request $request){
		$em = $this->getDoctrine()->getManager();
		$inputRepository = $this->getDoctrine()->getRepository(WorkInput::class);
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		$newInput = new WorkInput();
		$newInput->setProject($projectRepository->find($request->get('projectId')));
		$newInput->setUser($userRepository->find($this->get('session')->get('user')->getId()));
		$newInput->setWeekStart(new \DateTime($request->get('weekstart')));
		$newInput->setMon($request->get('mon'));
		$newInput->setTue($request->get('tue'));
		$newInput->setWed($request->get('wed'));
		$newInput->setThu($request->get('thu'));
		$newInput->setFri($request->get('fri'));
		$newInput->setComment($request->get('comments'));

		try{
			$em->persist($newInput);
			$em->flush();
			$arrData = ['success' => true, 'newId' => $newInput->getId()];
		}catch (\Exception $e) {
			$arrData = ['success' => false, 'errormsg' => $e->getMessage()];
		}

		return new JsonResponse($arrData);
	}

    /**
     * @Route("/overview", name="report_overview")
     */
    public function overview()    {
        return $this->redirect('planning_index');
	}

	/**
	 * @Route("/add/{reportId}", name="report_del")
	 */
	public function del(Request $request,$reportId){
		$em = $this->getDoctrine()->getManager();
		$inputRepository = $this->getDoctrine()->getRepository(WorkInput::class);

		if(!($input = $inputRepository->find($reportId))){
			$arrData = ['success' => false, 'errormsg' => 'Impossible de trouver la saisie demandée'];
		}elseif($input->getUser()->getId() != $this->get('session')->get('user')->getId()){
			$arrData = ['success' => false, 'errormsg' => 'Impossible de trouver la saisie demandée'];
		}else{
			$em->remove($input);
			$em->flush();
			$arrData = ['success' => true];
		}
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/edit", name="report_edit")
	 */
	public function edit(Request $request){
		$em = $this->getDoctrine()->getManager();
		$inputRepository = $this->getDoctrine()->getRepository(WorkInput::class);

		$inputId = $request->get('inputid');
		if($inputId == null){
			$arrData = ['success' => false, 'errormsg' => 'Aucune saisie demandée'];
		}elseif(!($input = $inputRepository->find($inputId))){
			$arrData = ['success' => false, 'errormsg' => 'Impossible de trouver la saisie demandée'];
		}elseif($input->getUser()->getId() != $this->get('session')->get('user')->getId()){
			$arrData = ['success' => false, 'errormsg' => 'Impossible de trouver la saisie demandée'];
		}else{
			$input->setMon($request->get('mon'));
			$input->setTue($request->get('tue'));
			$input->setWed($request->get('wed'));
			$input->setThu($request->get('thu'));
			$input->setFri($request->get('fri'));
			$input->setComment($request->get('comments'));
			$em->flush();
			$arrData = ['success' => true];
		}

		return new JsonResponse($arrData);
	}

}
