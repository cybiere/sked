<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Entity\Project;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AppExtension extends AbstractExtension
{
	private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
	}

    public function getFunctions()
    {
		return array(
			new TwigFunction('kanproject',array($this,'kanprojectFunction')),
        );
    }

	public function kanprojectFunction($project){
		echo "<div class='kanProject'>\n";
		echo "<h4>".$project->getName()." (".$project->getReference().")</h4>\n";
		echo "<p class='kanActions'>\n";
			echo "<a href='".$this->router->generate('project_edit',array("projectId"=>$project->getId()))."'><i title='Modifier' class='fas fa-edit'></i></a>\n";
			echo "<a href='".$this->router->generate('project_archive',array("projectId"=>$project->getId()))."'><i title='Archiver' class='fas fa-caret-square-down'></i></a>\n";
		echo "</p>\n";
		if($project->getStatus() != 7){
		echo "<p class='kanMove'>\n";
		if($project->getStatus() == 0)
			echo "&lt;\n";
		else
			echo "<a href='".$this->router->generate('project_index',array("projectId"=>$project->getId(),"way"=>"dec"))."'>&lt;</a>\n";
		if($project->getStatus() == 6)
			echo "&gt;\n";
		else
			echo "<a href='".$this->router->generate('project_index',array("projectId"=>$project->getId(),"way"=>"inc"))."'>&gt;</a>\n";
		echo "</p>\n";
		}
		echo "</div>\n";
	}
}
