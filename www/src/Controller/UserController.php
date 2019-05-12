<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Team;
use App\Entity\Planning;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Ldap\Ldap;

/**
 * @Route("/user")
 */
class UserController extends Controller{


	private function addUser($username,$isAdmin=false){
		$em = $this->getDoctrine()->getManager();
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		if($userRepository->findOneBy(['username' => $username])){
			$this->addFlash('danger',"Attention : l'utilisateur ".$username." existe déjà");
			return false;
		}else{
			$ldap = Ldap::create('ext_ldap', array('connection_string' => 'ldap://'.$this->container->getParameter('ldap_url').':'.$this->container->getParameter('ldap_port')));
			$ldap->bind($this->container->getParameter('ldap_bind_dn'), $this->container->getParameter('ldap_bind_pw'));

			$sanitized=array('\\' => '\5c','*' => '\2a','(' => '\28',')' => '\29',"\x00" => '\00');
			$username = str_replace(array_keys($sanitized),array_values($sanitized),$username);	

			$ldapQuery = $ldap->query($this->container->getParameter('ldap_base_dn'), '('.$this->container->getParameter('ldap_uid_key').'='.$username.')');
			$ldapResults = $ldapQuery->execute()->toArray();

			if(isset($ldapResults[0]) && $ldapResults[0]->getAttribute($this->container->getParameter('ldap_uid_key'))[0] == $username){
				if($ldapResults[0]->getAttribute('mail') == NULL){
					$this->addFlash('danger',"Erreur : l'utilisateur ".$username." n'a pas d'adresse email dans l'annuaire (champ \"mail\")");
					return false;
				}
				else
				{
					$user = new User();
					$user->setUsername($ldapResults[0]->getAttribute($this->container->getParameter('ldap_uid_key'))[0]);
					$user->setFullname($ldapResults[0]->getAttribute('cn')[0]);
					$user->setEmail($ldapResults[0]->getAttribute('mail')[0]);
					$user->setIsAdmin($isAdmin);
					$em->persist($user);
					$em->flush();
					return true;
				}
			}else{
				$this->addFlash('danger',"Erreur : l'utilisateur ".$username." n'existe pas dans l'annuaire LDAP");
				return false;
			}
		}
	}

	/**
	 * @Route("/",name="user_index")
	 */
	public function index(Request $request){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);

		$user = new User();
		$formBuilder = $this->createFormBuilder($user)->add('username', TextType::class,array('label'=>"Nom d'utilisateur"));
		if(!$this->container->hasParameter('ldap_url')){
			$formBuilder->add('fullname', TextType::class)
			   ->add('email', TextType::class);
		}
		$teams = $teamRepository->findAll();
		if($teams != NULL){
			$formBuilder->add('team', NULL);
		}
		$form = $formBuilder->add('save', SubmitType::class, array('label' => "Ajouter l'utilisateur"))->getForm();


		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$user = $form->getData();
			if($userRepository->findOneBy(['username' => $user->getUsername()])){
				$this->addFlash('warning',"Attention : l'utilisateur ".$user->getUsername()." existe déjà");
			}else{
				if($this->addUser($user->getUsername())){
					$newUser = $userRepository->findOneBy(['username' => $user->getUsername()]);
					$newUser->setTeam($user->getTeam());
					$em->flush();
					$this->addFlash('success','Utilisateur ajouté');
				}
			}
		}
		$users = $userRepository->findAll();
		return $this->render('user/index.html.twig',array('users'=>$users,'form'=>$form->createView(),'teams'=>$teams));
	}

	/**
	 * @Route("/{id}/team",name="user_changeTeam")
	 */
	public function changeTeam(Request $request,User $user){
		$em = $this->getDoctrine()->getManager();
		$teamRepository = $this->getDoctrine()->getRepository(Team::class);
		$teamId = $request->request->get('team');
		if($teamId == 0){
			$user->setTeam(NULL);
			$em->flush();
			$this->addFlash('success',"Équipe modifiée");
			return $this->redirectToRoute('user_index');
		}
		$team = $teamRepository->find($teamId);
		if(!$team){
			$this->addFlash('danger',"L'équipe recherchée n'existe pas");
		}else{
			$user->setTeam($team);
			$em->flush();
			$this->addFlash('success',"Équipe modifiée");
		}
		return $this->redirectToRoute('user_index');
	}

	/**
	 * @Route("/del/{username}",name="user_del")
	 */
	public function delete(Request $request,$username){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$user = $userRepository->findOneBy(['username' => $username]);
		if($user){
			if($user->isAdmin()){
				$this->addFlash('warning',"Vous ne pouvez pas supprimer un administrateur. Retirez lui les droits d'administrateur avant de le supprimer.");
			}elseif($user->getId() == $this->get('session')->get('user')->getId()){
				$this->addFlash('warning',"Vous ne pouvez pas supprimer votre propre compte.");
			}else{
				$this->addFlash('success','Utilisateur '.$username.' supprimé');
				$em->remove($user);
				$em->flush();
			}
		}else{
			$this->addFlash('danger',"Erreur : l'utilisateur ".$username." n'existe pas");
		}
		return $this->redirectToRoute('user_index');
	}

	/**
	 * @Route("/toggleResource/{userid}",name="user_toggleResource")
	 */
	public function toggleResource(Request $request,$userid){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$user = $userRepository->find($userid);
		if($user){
			$user->setIsResource($user->isResource()?false:true);
			$em->flush();
		}else{
			$this->addFlash('danger',"Erreur : l'utilisateur demandé n'existe pas");
		}
		return $this->redirectToRoute('user_index');
	}

	/**
	/**
	 * @Route("/toggleAdmin/{userid}",name="user_toggleAdmin")
	 */
	public function toggleAdmin(Request $request,$userid){
		if(!$this->get('session')->get('user')->isAdmin()){
			throw $this->createNotFoundException("Cette page n'existe pas");
		}

		$em = $this->getDoctrine()->getManager();
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$user = $userRepository->find($userid);
		if($user){
			if($user->getId() == $this->get('session')->get('user')->getId() && $user->isAdmin())
				$this->addFlash('warning',"Vous ne pouvez pas retirer vos droits d'administrateur");
			else
				$user->setIsAdmin($user->isAdmin()?false:true);
			$em->flush();
		}else{
			$this->addFlash('danger',"Erreur : l'utilisateur demandé n'existe pas");
		}
		return $this->redirectToRoute('user_index');
	}

	/**
	 * @Route("/enrol",name="user_enrol")
	 */
	public function enrolUser(){
		$em = $this->getDoctrine()->getManager();
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		$sanitized=array('\\' => '\5c','*' => '\2a','(' => '\28',')' => '\29',"\x00" => '\00');
		$username = str_replace(array_keys($sanitized),array_values($sanitized),$this->get('security.token_storage')->getToken()->getUser()->getUsername());	

		if(!($user = $userRepository->findOneBy(['username' => $username]))){
			if(!$userRepository->findAll()){
				if($this->addUser($username,true)){
					$this->addFlash('success','Bienvenue sur Sked');
					$this->addFlash('warning','En tant que premier utilisateur, vous avez les droits administrateur. Vous pouvez ajouter et gérer les utilisateurs dans l\'onglet "Utilisateurs"');
					$user = $userRepository->findOneBy(['username' => $username]);
				}else{
					return $this->redirectToRoute('logout');
				}
			}else{
				return $this->redirectToRoute('logout');
			}
		}

		$this->get('session')->set('user',$user);
		return $this->redirectToRoute('planning_index');
	}

	/**
	 * @Route("/profile/{userId}", name="user_view", defaults={"userId"=0},requirements={"userId"="\d+"})
	 */
	public function viewUser($userId=0){
		$em = $this->getDoctrine()->getManager();
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$planningRepository = $this->getDoctrine()->getRepository(Planning::class);

		if($this->get('session')->get('user')->isAdmin()){
			if(!($user = $userRepository->find($userId))){
				$user = $userRepository->find($this->get('session')->get('user')->getId());
			}
		}else{
			$user = $userRepository->find($this->get('session')->get('user')->getId());
		}

		$plannings = $planningRepository->findBy(
			array('user'=>$user->getId()),
			array('startDate'=>'ASC','startHour'=>'ASC'));

		$startDateObj = new \DateTime();

		$renderMonths = 3;

		$maxOffsets = [];
		for($i=0;$i<$renderMonths;$i++){
			$offDate = clone $startDateObj;
			$offDate->modify("+".$i."months");
			$maxOffsets[$i][$user->getId()] = CommonController::calcOffset($offDate,$user);
		}


		return $this->render('user/view.html.twig',array(
			'nbMonths' => 3,
			'maxOffsets' => $maxOffsets,
			'user'=>$user,
			'plannings'=>$plannings,
			'holidays'=>CommonController::getHolidays($startDateObj->format('Y'))
		));
	}
}
