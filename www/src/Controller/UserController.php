<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/user")
 */
class UserController {
	/**
	 * @Route("/")
	 */
	public function index(){
		return new Response("<html><body>OK</body></html>");
	}
}
