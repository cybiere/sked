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
	<div class='card kanProject' data-projectid="<?php echo htmlspecialchars($project->getId()); ?>">
		<h4 class="card-header text-center">
			<a class="text-dark" data-toggle="collapse" href="#kanDetails-<?php echo htmlspecialchars($project->getId()); ?>" aria-expanded="false">
				<?php echo htmlspecialchars($project->getClient()); ?> <?php echo htmlspecialchars($project->getName()); ?>
			</a>
		</h4>
		<div id="kanDetails-<?php echo htmlspecialchars($project->getId()); ?>" class="collapse">
			<div class="card-body">
				<ul class="list-unstyled">
					<li> <a href="<?php echo $this->router->generate('project_view',array("projectId"=>$project->getId())); ?>">Détails</a></li>
					<li> Code projet : <?php echo htmlspecialchars($project->getReference()); ?></li>
					<li> Nom : <?php echo htmlspecialchars($project->getName()); ?></li>
					<li> Client : <?php echo htmlspecialchars($project->getClient()); ?></li>
					<li> Jours vendus : <?php echo $project->getNbDays()?htmlspecialchars($project->getNbDays()):"?"; ?>jh</li>
					<li> Jours planifiés : <?php echo htmlspecialchars($project->getPlannedDays()); ?>jh</li>
				</ul>
			</div>
			<?php if($isAdmin){ ?>
			<div class="card-footer">
				<div class="row">
					<div class="col">
						<?php if($project->getStatus() == 0) echo "<i class='fas fa-chevron-circle-left'></i>"; else { ?>
							<a href='<?php echo $this->router->generate('project_movelink',array("projectId"=>$project->getId(),"way"=>"dec")); ?>'><i class='fas fa-chevron-circle-left'></i></a>
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
							<a href='<?php echo $this->router->generate('project_movelink',array("projectId"=>$project->getId(),"way"=>"inc")); ?>'><i class='fas fa-chevron-circle-right'></i></a>
						<?php } ?>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
<?php
	}


	public function printPlanningFunction($planning,$isAdmin,$project=0){
?>
	<div
		class="project <?php 
		if($project != 0 && ($planning->getProject() == NULL || $planning->getProject()->getId() != $project)){
				echo "otherProject";
			}
			elseif($planning->getProject() == NULL){
				echo "absence"; 
			}elseif($planning->getProject()->isBillable()){
				if($planning->isConfirmed()){
					echo $planning->isMeeting()?"meeting":"billable"; 
				}else{
					echo $planning->isMeeting()?"meeting-unconfirmed":"billable-unconfirmed"; 
				}
			}else{
				echo "non-billable"; 
			}
		?> "
		tabindex="0" 
		data-duration="<?php echo htmlspecialchars($planning->getNbSlices()); ?>" 
		data-planningId="<?php echo htmlspecialchars($planning->getId()); ?>"
		data-toggle="popover"
		data-html="true"
		title="<?php echo $planning->getProject() == NULL?"Absence":"<a href='".$this->router->generate('project_view',array("projectId"=>$planning->getProject()->getId()))."'>".htmlspecialchars($planning->getProject()->getName())."</a>"; ?>"
		data-content="
			<div class='row'>
<?php if($planning->getProject() != NULL){ ?>
			<dt class='col-md-6'>Code projet</dt><dd class='col-md-6'><?php echo htmlspecialchars($planning->getProject()->getReference()); ?></dd>
			<dt class='col-md-6'>Client</dt><dd class='col-md-6'><?php echo htmlspecialchars($planning->getProject()->getClient()); ?></dd>
			<dt class='col-md-6'>jh planif/vendus</dt><dd class='col-md-6'><?php echo htmlspecialchars($planning->getProject()->getPlannedDays())."/".htmlspecialchars($planning->getProject()->getNbDays()); ?></dd>
			<dt class='col-md-6'>Commentaires</dt><dd class='col-md-6'><?php echo nl2br(htmlspecialchars($planning->getProject()->getComments())); ?></dd>
<?php if($isAdmin && $project == 0){ ?>
			<div class='action col-md-4'><a class='btn btn-outline-success' href='<?php echo $this->router->generate('planning_confirm',array("planningId"=>$planning->getId())); ?>'><i class='far fa-check-circle'></i></a></div>
			<div class='action col-md-4'><a class='btn btn-outline-warning' href='<?php echo $this->router->generate('project_edit',array("projectId"=>$planning->getProject()->getId())); ?>'><i class='fas fa-edit'></i></a></div>
<?php } ?>
<?php } ?>
<?php if($isAdmin && $project == 0){ ?>
			<div class='action col-md-4'><a class='btn btn-outline-danger' href='<?php echo $this->router->generate('planning_del',array("planningId"=>$planning->getId())); ?>'><i class='fas fa-trash'></i></a></div>
<?php } ?>
			</div>
		"
	>
		<?php echo $planning->getProject() == NULL?"Absence":htmlspecialchars($planning->getProject()->getClient())." ".htmlspecialchars($planning->getProject()->getName()); ?>
		<i class="duration">
			<?php echo htmlspecialchars($planning->getNbSlices()/2); ?>
		</i>
	</div>

<?php
	}

}
