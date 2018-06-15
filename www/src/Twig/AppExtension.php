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

	public function kanprojectFunction($project,$isAdmin){
?>
	<div class='card kanProject' data-projectid="<?php echo $project->getId(); ?>">
		<h4 class="card-header text-center">
			<a class="text-dark" data-toggle="collapse" href="#kanDetails-<?php echo $project->getId(); ?>" aria-expanded="false">
				<?php echo $project->getClient(); ?> <?php echo $project->getName(); ?>
			</a>
		</h4>
		<div id="kanDetails-<?php echo $project->getId(); ?>" class="collapse">
			<div class="card-body">
				<ul class="list-unstyled">
					<li> Code projet : <?php echo $project->getReference(); ?></li>
					<li> Nom : <?php echo $project->getName(); ?></li>
					<li> Client : <?php echo $project->getClient(); ?></li>
					<li> Jours vendus : <?php echo $project->getNbDays()?$project->getNbDays():"?"; ?>jh</li>
					<li> Jours planifiés : <?php echo $project->getPlannedDays(); ?>jh</li>
				</ul>
			</div>
			<?php if($isAdmin){ ?>
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
			<?php } ?>
		</div>
	</div>
<?php
	}


	public function printPlanningFunction($planning,$isAdmin){
?>
	<div 
		class="project <?php if($planning->getProject() == NULL) echo "absence"; elseif($planning->getProject()->isBillable()) echo $planning->isMeeting()?"meeting":"billable"; else echo "non-billable"; ?>"
		tabindex="0" 
		data-duration="<?php echo $planning->getNbSlices(); ?>" 
		data-planningId="<?php echo $planning->getId(); ?>"
		data-toggle="popover"
		data-html="true"
		title="<?php echo $planning->getProject() == NULL?"Absence":$planning->getProject()->getName(); ?>"
		data-content="
			<div class='row'>
<?php if($planning->getProject() != NULL){ ?>
			<dt class='col-md-6'>Code projet</dt><dd class='col-md-6'><?php echo $planning->getProject()->getReference(); ?></dd>
			<dt class='col-md-6'>Client</dt><dd class='col-md-6'><?php echo $planning->getProject()->getClient(); ?></dd>
			<dt class='col-md-6'>jh planif/vendus</dt><dd class='col-md-6'><?php echo $planning->getProject()->getPlannedDays()."/".$planning->getProject()->getNbDays(); ?></dd>
			<dt class='col-md-6'>Commentaires</dt><dd class='col-md-6'><?php echo $planning->getProject()->getComments(); ?></dd>
<?php if($isAdmin){ ?>
			<div class='action col-md-6'><a class='btn btn-outline-warning' href='<?php echo $this->router->generate('project_edit',array("projectId"=>$planning->getProject()->getId())); ?>'><i class='fas fa-edit'></i></a></div>
<?php } ?>
<?php } ?>
<?php if($isAdmin){ ?>
			<div class='action col-md-6'><a class='btn btn-outline-danger' href='<?php echo $this->router->generate('planning_del',array("planningId"=>$planning->getId())); ?>'><i class='fas fa-trash'></i></a></div>
<?php } ?>
			</div>
		"
	>
		<?php echo $planning->getProject() == NULL?"Absence":$planning->getProject()->getClient()." ".$planning->getProject()->getName(); ?>
		<i class="duration">
			<?php echo $planning->getNbSlices()/2; ?>
		</i>
	</div>

<?php
	}

}
