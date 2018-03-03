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
		echo "<p class='kanMove'>\n";
		echo "<a href='".$this->router->generate('project_index',array("projectId"=>$project->getId(),"way"=>"dec"))."'>&lt;</a>\n";
		echo "<a href='".$this->router->generate('project_index',array("projectId"=>$project->getId(),"way"=>"inc"))."'>&gt;</a>\n";
		echo "</p>\n";
		echo "</div>\n";
	}
}
