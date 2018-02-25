<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Ldap\Ldap;

/**
 * @Route("/user")
 */
class UserController extends Controller{
	/**
	 * @Route("/",name="user_index")
	 */
	public function index(){
		return $this->render('user/index.html.twig',array('userList'=>array('titi','toto')));
	}

	/**
	 * @Route("/enrol",name="user_enrol")
	 */
	public function enrol(){
		$ldap = Ldap::create('ext_ldap', array('connection_string' => 'ldap://'.$this->container->getParameter('ldap_url').':'.$this->container->getParameter('ldap_port')));
		$ldap->bind($this->container->getParameter('ldap_bind_dn'), $this->container->getParameter('ldap_bind_pw'));
		$ldapQuery = $ldap->query($this->container->getParameter('ldap_base_dn'), '(&(objectclass=person)(ou=Scientists))');
		$ldapResults = $ldapQuery->execute()->toArray();

		$userLdap = array();

		foreach($ldapResults as $result){
			$userLdap[] = $result->getAttribute('cn')[0];
		}


		$userList = array('titi','toto');
		return $this->render('user/enrol.html.twig',array('userList'=>$userList,'userLdap'=>$userLdap,'rawLdap'=>$ldapResults));
	}
}
