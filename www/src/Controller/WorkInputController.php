<?php

namespace App\Controller;

use App\Entity\WorkInput;
use App\Form\WorkInputType;

use Symfony\Component\HttpFoundation\Request;
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

		$myInputs = $inputRepository->findByUser($this->get('session')->get('user')->getId());

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


		return $this->render('work_input/index.html.twig', [
			'holidays' => $holidays,
			'inputs'=>$myInputs,
			'startDate'=>$startDate,
		]);
	}

    /**
     * @Route("/overview", name="report_overview")
     */
    public function overview()
    {
        return $this->render('work_input/index.html.twig', []);
    }
}
