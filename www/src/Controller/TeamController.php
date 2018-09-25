<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TeamController extends Controller
{
    /**
     * @Route("/team", name="team")
     */
    public function index()
    {
        return $this->render('team/index.html.twig', [
            'controller_name' => 'TeamController',
        ]);
    }
}
