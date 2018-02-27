<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ProjectStatusController extends Controller
{
    /**
     * @Route("/project/status", name="project_status")
     */
    public function index()
    {
        return $this->render('project_status/index.html.twig', [
            'controller_name' => 'ProjectStatusController',
        ]);
    }
}
