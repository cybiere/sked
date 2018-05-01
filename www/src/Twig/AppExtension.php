<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Entity\Project;
use App\Entity\Planning;
use App\Entity\User;
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
			new TwigFunction('printPlanning',array($this,'printPlanningFunction')),
		);
	}

	public function kanprojectFunction($project){
?>
	<div class='card kanProject' data-projectid="<?php echo $project->getId(); ?>">
		<h4 class="card-header">
			<?php echo $project->getReference(); ?>
<a class="btn bnt-xs btn-outline-info float-right" data-toggle="collapse" href="#kanDetails-<?php echo $project->getId(); ?>" role="button" aria-expanded="false" aria-controls="addButton">
				<i title='Détails' class='fas fa-angle-double-down'></i>
			</a>
		</h4>
		<div id="kanDetails-<?php echo $project->getId(); ?>" class="collapse">
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
	</div>
<?php
	}


	public function printPlanningFunction($planning){
?>

	<div 
		class="project" 
		tabindex="0" 
		data-duration="<?php echo $planning->getNbSlices(); ?>" 
		data-planningId="<?php echo $planning->getId(); ?>"
		data-toggle="tooltip"
		data-html="true"
		title="<em>Tooltip</em> <u>with</u> <b>HTML</b>"
	>
		<?php echo $planning->getProject()->getName(); ?>
		<i class="duration">
			<?php echo $planning->getNbSlices(); ?>
		</i>
	</div>

<?php
	}

}
