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
			$userRepository = $this->getDoctrine()->getRepository(User::class);

			//check the user is not already existing
			if($userRepository->findOneBy(['username' => $user->getUsername()])){
				echo "<script>alert('user already enroled')</script>";
			}else{
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
		}

		$users = $userRepository->findAll();

		return $this->render('user/index.html.twig',array('users'=>$users,'form'=>$form->createView()));
	}
}
