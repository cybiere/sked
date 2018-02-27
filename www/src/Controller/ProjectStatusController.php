<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/project/status")
 */
class ProjectStatusController extends Controller
{
    /**
     * @Route("/", name="projectstatus_index")
     */
    public function index()
    {
        return $this->render('project_status/index.html.twig', [
            'controller_name' => 'ProjectStatusController',
        ]);
    }
}
