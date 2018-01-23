<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


/**
 * @Route("/user")
 */
class UserController extends Controller{
	/**
	 * @Route("/")
	 */
	public function index(){
		return $this->render('user/index.html.twig',array('userList'=>array('titi','toto')));
	}
}
