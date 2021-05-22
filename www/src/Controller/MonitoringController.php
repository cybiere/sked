<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Team;
use App\Entity\Planning;
use App\Entity\Project;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\HttpFoundation\JsonResponse;

class MonitoringController extends Controller{


	/**
	 * @Route("/monitoring", name="monitoring_index")
	 * @Route("/monitoring/{startDate}", name="monitoring_index_shift", defaults={"startDate"="now"}, methods={"GET"})
	 */
	public function index(Request $request, $startDate="now") {
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());
		$teams = $teamRepository->findAll();
		$users = $userRepository->findAll();

		try {
			$startDateObj = new \DateTime($startDate);
		} catch (\Exception $e) {
			$startDate = "now";
			$startDateObj = new \DateTime("now");
		}
		$renderMonths = 3;

		$planning = array();

		for ($nbmonth = 0; $nbmonth < $renderMonths; $nbmonth++) {
			$month = clone $startDateObj;
			$month->modify("+{$nbmonth} month");
			$from = clone $month->modify("first day of this month");
			$from->modify('midnight');
			$to = clone $month->modify("last day of this month");
			$to->modify('tomorrow');

			foreach ($users as $user) {
				// monthly
				$planning[$user->getId()][] = array(
					'startDate' => clone $from,
					'nbSlices' => false,
					'data' => $user->getTace(
						$from,
						$to
					)
				);

				$daterange = new \DatePeriod(
					$from,
					new \DateInterval('P1D'),
					$to
				);

				$days = 0;
				$start = null;

				$last = clone $to;
				$last->modify("-1 day");

				// iterate over period day by day
				foreach ($daterange as $date) {
					if ($days == 0) {
						$start = clone $date;
					}

					if ($date->format("N") == 7) {
						$days = 0;
						continue;
					}

					if (
						$date->format("N") == 6 ||
						$last == $date
					) {
						if ($last == $date && $date->format("N") != 6) {
							$nbslices = (4 + (4 * $days));
							$end = clone $date;
							$end->modify("+1 day");
						} else {
							$nbslices = (4 * $days);
							$end = clone $date;
						}


						// weekly
						$planning[$user->getId()][] = array(
							'startDate' => $start,
							'nbSlices' => $nbslices,
							'data' => $user->getTace(
								$start,
								$end
							)
						);
					}

					$days++;
				}
			}
		}

		return $this->render(
			'monitoring/index.html.twig',
			array(
				'startDate' => $startDate,
				'nbMonths' => 3,
				'holidays' => CommonController::getHolidays($startDateObj->format('Y')),
				'users' => $users,
				'me' => $me,
				'plannings' => $planning
				)
			);
	}

	/**
	 * @Route("/export/monitoring", name="monitoring_export")
	 * @Route("/export/monitoring/{startDate}", name="monitoring_export_shift", defaults={"startDate"="now"}, methods={"GET"})
	 */
	public function export(Request $request, $startDate="now") {

		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$projectRepository = $this->getDoctrine()->getRepository(Project::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$me = $userRepository->find($this->get('session')->get('user')->getId());
		$teams = $teamRepository->findAll();
		$users = $userRepository->findAll();

		try {
			$startDateObj = new \DateTime($startDate);
		} catch (\Exception $e) {
			$startDate = "now";
			$startDateObj = new \DateTime("now");
		}
		$renderMonths = 3;

		$calendars = array();
		$calendar = array();

		for ($nbmonth = 0; $nbmonth < $renderMonths; $nbmonth++) {
			$month = clone $startDateObj;
			$month->modify("+{$nbmonth} month");
			$from = clone $month->modify("first day of this month");
			$from->modify('midnight');
			$to = clone $month->modify("last day of this month");
			$to->modify('tomorrow');

			foreach ($users as $user) {
				$calendar[$user->getId()] = $user->getUsername();
				// monthly
				$calendars[$from->format('Y-m-d')."-".$to->format('Y-m-d')][$user->getId()] = array(
					'startDate' => clone $from,
					'end' => $to,
					'data' => $user->getTace(
						$from,
						$to
					)
				);

				$daterange = new \DatePeriod(
					$from,
					new \DateInterval('P1D'),
					$to
				);

				$days = 0;
				$start = null;

				$last = clone $to;
				$last->modify("-1 day");

				// iterate over period day by day
				foreach ($daterange as $date) {
					if ($days == 0) {
						$start = clone $date;
					}

					if ($date->format("N") == 7) {
						$days = 0;
						continue;
					}

					if (
						$date->format("N") == 6 ||
						$last == $date
					) {
						if ($last == $date && $date->format("N") != 6) {
							$nbslices = (4 + (4 * $days));
							$end = clone $date;
							$end->modify("+1 day");
						} else {
							$nbslices = (4 * $days);
							$end = clone $date;
						}


						// weekly
						$calendars[$start->format('Y-m-d')."-".$end->format('Y-m-d')][$user->getId()] = array(
							'start' => $start,
							'end' => $end,
							'data' => $user->getTace(
								$start,
								$end
							)
						);
					}

					$days++;
				}
			}
		}

		$response = $this->render('monitoring/export.csv.twig', [
			'calendar' => $calendar,
			'calendars' => $calendars
		]);

		$response->headers->set('Content-Type', 'text/csv');
		$response->headers->set('Content-Disposition', 'attachment; filename="monitoring.csv"');

		return $response;
	}
}