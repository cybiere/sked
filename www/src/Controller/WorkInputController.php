<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/report")
 */
class WorkInputController extends Controller
{
    /**
     * @Route("/", name="report_index")
     */
    public function index()
    {
        return $this->render('work_input/index.html.twig', []);
	}

    /**
     * @Route("/overview", name="report_overview")
     */
    public function overview()
    {
        return $this->render('work_input/index.html.twig', []);
    }
}
