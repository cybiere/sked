<?php

namespace App\Controller;

use App\Entity\User;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PlanningController extends Controller
{
    /**
     * @Route("/", name="planning_index")
     */
    public function index()
	{
		$em = $this->getDoctrine()->getManager();
		$userRepository = $this->getDoctrine()->getRepository(User::class);

		$users = $userRepository->findAll();

        return $this->render('planning/index.html.twig', [
            'users' => $users,
        ]);
    }
}
