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
?>
		<div class='card kanProject'>
		<h4 class="card-header"><?php echo $project->getReference(); ?></h4>
		<div class="card-body">
			<ul class="list-unstyled">
<li> Nom : <?php echo $project->getName(); ?></li>
<li> Client : <?php echo $project->getClient(); ?></li>
<li> Jours : <?php echo $project->getNbDays(); ?></li>
</ul>
</div>
<div class="card-footer">
	<div class="row">
	<div class="col">
		<?php if($project->getStatus() == 0) echo "<i class='fas fa-chevron-circle-left'></i>"; else { ?>
			<a href='<?php echo $this->router->generate('project_index',array("projectId"=>$project->getId(),"way"=>"dec")); ?>'><i class='fas fa-chevron-circle-left'></i></a>
		<?php } ?>
	</div>
	<div class="col">
			<a href='<?php echo $this->router->generate('project_edit',array("projectId"=>$project->getId())); ?>'><i title='Modifier' class='fas fa-edit'></i></a>
	</div>
	<div class="col">
			<a href='<?php echo $this->router->generate('project_archive',array("projectId"=>$project->getId())); ?>'><i title='Archiver' class='fas fa-caret-square-down'></i></a>
	</div>
	<div class="col">
		<?php if($project->getStatus() == 6) echo "<i class='fas fa-chevron-circle-right'></i>"; else { ?>
			<a href='<?php echo $this->router->generate('project_index',array("projectId"=>$project->getId(),"way"=>"inc")); ?>'><i class='fas fa-chevron-circle-right'></i></a>
		<?php } ?>
		</div>
		</div>
		</div>
		</div>
<?php
	}
}
