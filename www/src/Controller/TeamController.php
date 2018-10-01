<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Entity\ProjectStatus;
use App\Form\TeamType;
use App\Form\ProjectStatusType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/team")
 */
class TeamController extends Controller
{
    /**
	 * @Route("/{teamId}", name="team_index", defaults={"teamId"=0},requirements={"teamId"="\d+"})
     */
	public function index(Request $request, $teamId=0)
	{
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);

		if(!($team = $teamRepository->find($teamId))){
			$team = new Team();
		}

		$form = $this->createForm(TeamType::class,$team);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$em->persist($team);
			$em->flush();
			$this->addFlash('success','Équipe enregistrée');
			return $this->redirectToRoute('team_index');
		}

		$teams = $teamRepository->findAll();
		return $this->render('team/index.html.twig', [
			"form"=>$form->createView(),
			"teams"=>$teams
        ]);
	}

	/**
	 * @Route("/del/{teamId}",name="team_del")
	 */
	public function del(Request $request,$teamId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);

		if(!($team = $teamRepository->find($teamId))){
			$this->addFlash('danger','Erreur : équipe non trouvée');
		}else{
			$em->remove($team);
			$em->flush();
		}
		$referer = $request->headers->get('referer');
		return $this->redirect($referer);
	}

	/**
	 * @Route("/view/{teamId}",name="team_view")
	 */
	public function view(Request $request,$teamId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$projectStatusRepository = $this->getDoctrine()->getRepository(projectStatus::class);

		if(!($team = $teamRepository->find($teamId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$projectStatus = new ProjectStatus();
		$form = $this->createForm(ProjectStatusType::class,$projectStatus);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$projectStatus->setTeam($team);
			$projectStatus->setStatusOrder($projectStatusRepository->findMaxOrder($team)+1);
			$em->persist($projectStatus);
			$em->flush();
			$this->addFlash('success','Statut enregistré');
		}

		return $this->render('team/view.html.twig', [
			"team"=>$team,
			"users"=>$userRepository->findAll(),
			"form"=>$form->createView()
        ]);
	}

	/**
	 * @Route("/addMember/{teamId}/{userId}",name="team_addMember")
	 */
	public function addMember(Request $request,$teamId,$userId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		if(!($team = $teamRepository->find($teamId))){
			$arrData = ['success' => false, 'errormsg' => 'Équipe non trouvée'];
		}elseif(!($user = $userRepository->find($userId))){
			$arrData = ['success' => false, 'errormsg' => 'Utilisateur non trouvé'];
		}else{
			$team->addUser($user);
			$em->flush();
			$arrData = ['success' => true];
		}
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/delMember/{teamId}/{userId}",name="team_delMember")
	 */
	public function delMember(Request $request,$teamId,$userId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		if(!($team = $teamRepository->find($teamId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}elseif(!($user = $userRepository->find($userId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}else{
			$team->removeUser($user);
			$em->flush();
		}
		$referer = $request->headers->get('referer');
		return $this->redirect($referer);
	}

	/**
	 * @Route("/addManager/{teamId}/{userId}",name="team_addManager")
	 */
	public function addManager(Request $request,$teamId,$userId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		if(!($team = $teamRepository->find($teamId))){
			$arrData = ['success' => false, 'errormsg' => 'Équipe non trouvée'];
		}elseif(!($user = $userRepository->find($userId))){
			$arrData = ['success' => false, 'errormsg' => 'Utilisateur non trouvé'];
		}else{
			$team->addManager($user);
			$em->flush();
			$arrData = ['success' => true];
		}
		return new JsonResponse($arrData);
	}

	/**
	 * @Route("/delManager/{teamId}/{userId}",name="team_delManager")
	 */
	public function delManager(Request $request,$teamId,$userId){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		if(!($team = $teamRepository->find($teamId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}elseif(!($user = $userRepository->find($userId))){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}else{
			$team->removeManager($user);
			$em->flush();
		}
		$referer = $request->headers->get('referer');
		return $this->redirect($referer);
	}

}
