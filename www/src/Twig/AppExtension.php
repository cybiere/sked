<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Entity\Project;

class AppExtension extends AbstractExtension
{
    public function getFunctions()
    {
		return array(
			new TwigFunction('kanproject',array($this,'kanprojectFunction')),
        );
    }

	public function kanprojectFunction($project){
		echo "<div class='kanProject'>\n";
		echo "<h4>".$project->getName()." (".$project->getReference().")</h4>\n";
		echo "</div>";
	}
}
