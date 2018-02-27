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

class UserController extends Controller{
	/**
	 * @Route("/users/",name="users")
	 */
	public function index(Request $request){

		$em = $this->getDoctrine()->getManager();

		$user = new User();
		$form = $this->createFormBuilder($user)
			->add('username', TextType::class)
			->add('save', SubmitType::class, array('label' => 'Create User'))
			->getForm();


		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$user = $form->getData();

			//check the user is an existing LDAP user
			$ldap = Ldap::create('ext_ldap', array('connection_string' => 'ldap://'.$this->container->getParameter('ldap_url').':'.$this->container->getParameter('ldap_port')));
			$ldap->bind($this->container->getParameter('ldap_bind_dn'), $this->container->getParameter('ldap_bind_pw'));
			$ldapQuery = $ldap->query($this->container->getParameter('ldap_base_dn'), '(&(objectclass=person)(uid='.$user->getUsername().'))');
			$ldapResults = $ldapQuery->execute()->toArray();

			if(isset($ldapResults[0]) && $ldapResults[0]->getAttribute('uid')[0] == $user->getUsername()){
				$user->setFullname($ldapResults[0]->getAttribute('cn')[0]);
				$user->setEmail($ldapResults[0]->getAttribute('mail')[0]);

				$em->persist($user);
				$em->flush();
				echo "<script>alert('user ".$user->getFullname()." found')</script>";
				$user = new User();
				$form = $this->createFormBuilder($user)
					->add('username', TextType::class)
					->add('save', SubmitType::class, array('label' => 'Create User'))
					->getForm();
			}else{
				echo "<script>alert('user not found')</script>";
			}
		}


		$userRepository = $this->getDoctrine()->getRepository(User::class);
		$users = $userRepository->findAll();

		return $this->render('user/index.html.twig',array('users'=>$users,'form'=>$form->createView()));
	}
}
