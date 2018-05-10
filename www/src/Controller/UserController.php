<?php
namespace App\Controller;

use App\Entity\User;

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
	/**
	 * @Route("/",name="user_index")
	 */
	public function index(Request $request){

		$em = $this->getDoctrine()->getManager();
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		$user = new User();
		$formBuilder = $this->createFormBuilder($user)->add('username', TextType::class);
		if(!$this->container->hasParameter('ldap_url')){
			$formBuilder->add('fullname', TextType::class)
				->add('email', TextType::class);
		}
		$form = $formBuilder->add('save', SubmitType::class, array('label' => 'Create User'))->getForm();


		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$user = $form->getData();

			//check the user is not already existing
			if($userRepository->findOneBy(['username' => $user->getUsername()])){
				$this->addFlash('warning',"Attention : l'utilisateur ".$user->getUsername()." existe déjà");
			}else{
				if($this->container->hasParameter('ldap_url')){
					//check the user is an existing LDAP user
					$ldap = Ldap::create('ext_ldap', array('connection_string' => 'ldap://'.$this->container->getParameter('ldap_url').':'.$this->container->getParameter('ldap_port')));
					$ldap->bind($this->container->getParameter('ldap_bind_dn'), $this->container->getParameter('ldap_bind_pw'));

					$sanitized=array('\\' => '\5c','*' => '\2a','(' => '\28',')' => '\29',"\x00" => '\00');
					$username = str_replace(array_keys($sanitized),array_values($sanitized),$user->getUsername());	

					$ldapQuery = $ldap->query($this->container->getParameter('ldap_base_dn'), '(&(objectclass=person)(uid='.$username.'))');
					$ldapResults = $ldapQuery->execute()->toArray();

					if(isset($ldapResults[0]) && $ldapResults[0]->getAttribute('uid')[0] == $user->getUsername()){
						$user->setFullname($ldapResults[0]->getAttribute('cn')[0]);
						$user->setEmail($ldapResults[0]->getAttribute('mail')[0]);
						$em->persist($user);
						$em->flush();
						$this->addFlash('success','Utilisateur ajouté');
					}else{
						$this->addFlash('danger',"Erreur : l'utilisateur ".$username." n'existe pas dans l'annuaire LDAP");
					}
				}else{
					$em->persist($user);
					$em->flush();
					$this->addFlash('success','Utilisateur ajouté');
				}
				$user = new User();
				$formBuilder = $this->createFormBuilder($user)->add('username', TextType::class);
				if(!$this->container->hasParameter('ldap_url')){
					$formBuilder->add('fullname', TextType::class)
						->add('email', TextType::class);
				}
				$form = $formBuilder->add('save', SubmitType::class, array('label' => 'Create User'))->getForm();
			}
		}

		$users = $userRepository->findAll();

		return $this->render('user/index.html.twig',array('users'=>$users,'form'=>$form->createView()));
	}

	/**
	 * @Route("/del/{username}",name="user_del")
	 */
	public function delete(Request $request,$username){
		$em = $this->getDoctrine()->getManager();
		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$user = $userRepository->findOneBy(['username' => $username]);
		if($user){
			$this->addFlash('success','Utilisateur '.$username.' supprimé');
			$em->remove($user);
			$em->flush();
		}else{
			$this->addFlash('danger',"Erreur : l'utilisateur ".$username." n'existe pas");
		}
		return $this->redirectToRoute('user_index');
	}

	/**
	 * @Route("/toggleResource/{userid}",name="user_toggleResource")
	 */
	public function toggleResource(Request $request,$userid){
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
}
