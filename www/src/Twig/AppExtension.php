<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Entity\Project;
use App\Entity\Planning;
use App\Entity\User;
use App\Entity\Team;
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
			new TwigFunction('printTask',array($this,'printTaskFunction')),
			new TwigFunction('printTeam',array($this,'printTeamFunction')),
		);
	}

	public function kanprojectFunction($project,$me){
		$isAdmin = $me?$me->canAdmin($project):false;
?>
	<div class='card kanProject<?php if($isAdmin){ echo " hasAdmin"; }?>' data-projectid="<?php echo htmlspecialchars($project->getId()); ?>">
		<h4 class="card-header text-center">
			<a class="text-dark" data-toggle="collapse" href="#kanDetails-<?php echo htmlspecialchars($project->getId()); ?>" aria-expanded="false">
				<?php echo htmlspecialchars($project->getClient()); ?> <?php echo htmlspecialchars($project->getName()); ?>
			</a>
		</h4>
		<div id="kanDetails-<?php echo htmlspecialchars($project->getId()); ?>" class="collapse">
			<div class="card-body">
				<ul class="list-unstyled">
					<li> <a href="<?php echo $this->router->generate('project_view',array("projectId"=>$project->getId())); ?>">Détails</a></li>
					<?php if($project->getTeam()){ echo "<li> Équipe : ".htmlspecialchars($project->getTeam()->getName())."</li>"; } ?>
					<li> Code projet : <?php echo htmlspecialchars($project->getReference()); ?></li>
					<li> Nom : <?php echo htmlspecialchars($project->getName()); ?></li>
					<li> Client : <?php echo htmlspecialchars($project->getClient()); ?></li>
					<li> Responsable projet : <?php if($project->getProjectManager()){
							if($isAdmin){
								echo'<a href="'.$this->router->generate('user_view',array("userId"=>$project->getProjectManager()->getId())).'">'.htmlspecialchars($project->getProjectManager()->getFullname()).'</a>';
							}else{
								echo htmlspecialchars($project->getProjectManager()->getFullname());
							}
						} ?></li>
					<li> Jours vendus : <?php echo $project->getNbDays()?htmlspecialchars($project->getNbDays()):"?"; ?>jh</li>
					<li> Jours planifiés : <?php echo htmlspecialchars($project->getPlannedDays()); ?>jh</li>
				</ul>
			</div>
			<?php if($isAdmin){ ?>
			<div class="card-footer">
				<div class="row">
					<div class="col">
						<a href='<?php echo $this->router->generate('project_edit',array("projectId"=>$project->getId())); ?>'><i title='Modifier' class='fas fa-edit'></i></a>
					</div>
					<div class="col">
						<a href='<?php echo $this->router->generate('project_archive',array("projectId"=>$project->getId())); ?>'><i title='Archiver' class='fas fa-caret-square-down'></i></a>
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
		if($project == 0 && $isAdmin){
			echo " hasAdmin";
		}
		if ($planning->isMeetup()) { echo " meetup"; }
		if ($planning->isDeliverable()) { echo " deliverable"; }
		if ($planning->isCapitalization()) { echo " capitalization"; }
		if (! $planning->isMonitoring()) { echo " monitoring"; }
		?>"
		tabindex="0" 
		data-duration="<?php echo htmlspecialchars($planning->getNbSlices()); ?>" 
		data-planningId="<?php echo htmlspecialchars($planning->getId()); ?>"
		data-toggle="popover"
		data-html="true"
		id="planning-<?php echo $planning->getId(); ?>"
		data-id="<?php echo $planning->getId(); ?>"
		data-comments="<?php echo addslashes($planning->getComments()); ?>"
		title="
<?php 
		if($planning->getTask() != NULL){
			if($planning->getTask()->getComments()){ 
				echo "<a href='#' data-toggle='tooltip' title='".htmlspecialchars($planning->getTask()->getComments())."'>".htmlspecialchars($planning->getTask()->getName())."</a>";
			}else{
				echo htmlspecialchars($planning->getTask()->getName());
			}
		}else 
			echo $planning->getProject() == NULL?"Absence":"<a href='".$this->router->generate('project_view',array("projectId"=>$planning->getProject()->getId()))."'>".htmlspecialchars($planning->getProject()->getName())."</a>"; ?>
"
		data-content="
			<div class='row'>
<?php if($planning->getProject() != NULL){ ?>
<?php if($planning->getTask() != NULL){ ?>
			<dt class='col-md-6'>Projet</dt><dd class='col-md-6'><a href='<?php echo $this->router->generate('project_view',array("projectId"=>$planning->getProject()->getId()))."'>".htmlspecialchars($planning->getProject()->getName()); ?></a></dd>
<?php } ?>
			<dt class='col-md-6'>Code projet</dt><dd class='col-md-6'><?php echo htmlspecialchars($planning->getProject()->getReference()); ?></dd>
			<dt class='col-md-6'>Client</dt><dd class='col-md-6'><?php echo htmlspecialchars($planning->getProject()->getClient()); ?></dd>
			<dt class='col-md-6'>Responsable projet</dt><dd class='col-md-6'><?php if($planning->getProject()->getProjectManager()) echo htmlspecialchars($planning->getProject()->getProjectManager()->getFullname()); ?></dd>
			<dt class='col-md-6'>jh planif/vendus</dt><dd class='col-md-6'><?php echo htmlspecialchars($planning->getProject()->getPlannedDays())."/".htmlspecialchars($planning->getProject()->getNbDays()); ?></dd>
<?php if($planning->getComments()){ ?>
			<dt class='col-md-6' onclick='editPlanningComment(<?php echo $planning->getId(); ?>)'>Commentaires</dt><dd class='col-md-6' onclick='editPlanningComment(<?php echo $planning->getId(); ?>)'><?php echo nl2br(htmlspecialchars($planning->getComments())); ?></dd>
<?php } else { ?>
			<dt class='col-md-6'>Commentaires</dt><dd class='col-md-6'><?php echo nl2br(htmlspecialchars($planning->getProject()->getComments())); ?></dd>
<?php } ?>
<?php if($isAdmin && $project == 0){ ?>
<div class='popupaction col-md-3'><button class='btn btn-outline-success' onclick='confirmPlanning(<?php echo $planning->getId(); ?>)'><i class='far fa-check-circle'></i></button></div>
			<?php if($planning->getProject()->isBillable()){ ?>
			<div class='popupaction col-md-3'><button class='btn btn-outline-info' onclick='meetingPlanning(<?php echo $planning->getId(); ?>)'><i class='fas fa-exclamation-circle'></i></button></div>
			<?php } ?>
			<div class='popupaction col-md-3'><button class='btn btn-outline-info' onclick='deliverablePlanning(<?php echo $planning->getId(); ?>)'><i class='far fa-envelope'></i></button></div>
			<div class='popupaction col-md-3'><button class='btn btn-outline-info' onclick='meetupPlanning(<?php echo $planning->getId(); ?>)'><i class='fas fa-users'></i></button></div>
			<div class='popupaction col-md-3'><button class='btn btn-outline-info' onclick='capitalizationPlanning(<?php echo $planning->getId(); ?>)'><i class='far fa-money-bill-alt'></i></button></div>
			<div class='popupaction col-md-3'><a class='btn btn-outline-warning' href='<?php echo $this->router->generate('project_edit',array("projectId"=>$planning->getProject()->getId())); ?>'><i class='fas fa-edit'></i></a></div>
			<div class='popupaction col-md-3'><button class='btn btn-outline-info' onclick='editPlanningComment(<?php echo $planning->getId(); ?>)'><i class='far fa-comments'></i></button></div>
<?php } ?>
<?php } ?>
<?php if($isAdmin && $project == 0){ ?>
<div class='popupaction col-md-3'><button class='btn btn-outline-danger' onclick='delPlanning(<?php echo $planning->getId(); ?>)'><i class='fas fa-trash'></i></button></div>
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

	public function printTaskFunction($planning,$isAdmin,$project=0){
?>
	<div
		class="project neutral"
		tabindex="0" 
		data-duration="<?php echo htmlspecialchars($planning->getNbSlices()); ?>" 
		data-planningId="<?php echo htmlspecialchars($planning->getId()); ?>"
		data-toggle="popover"
		data-html="true"
		id="planning-<?php echo $planning->getId(); ?>"
		data-id="<?php echo $planning->getId(); ?>"
		data-comments="<?php echo addslashes($planning->getComments()); ?>"
	>
		<?php echo ($planning->getTask())->getName(); ?>

		<i class="duration">
			<?php echo htmlspecialchars($planning->getNbSlices()/2); ?>
		</i>
	</div>

<?php
	}

	public function printTeamFunction($team){
?>
	<tr>
		<td>
		<?php 
			$i=1;
			while($team->getLevel() >= $i){
				echo "&emsp;";
				$i++;
			}
			if($team->getLevel() != 0) echo "↳ ";
			echo "<a href='".$this->router->generate('team_view',array("teamId"=>$team->getId()))."'>".htmlspecialchars($team->getName())."</a>";
		?>
		</td>
		<td><?php echo count($team->getUsers()); ?></td>
		<td><?php foreach($team->getManagers() as $user){ echo "<span class='managerList'>".htmlspecialchars($user->getFullname())."</span>";} ?></td>
		<td class="actions">
			<a href='<?php echo $this->router->generate('team_view',array("teamId"=>$team->getId())); ?>'><i title='Détails' class='fa fa-search'></i></a>
			<a href='<?php echo $this->router->generate('team_edit',array("teamId"=>$team->getId())); ?>'><i title='Modifier' class='fas fa-edit'></i></a>
			<a class="text-danger" href='<?php echo $this->router->generate('team_del',array("teamId"=>$team->getId())); ?>'><i title="Supprimer" class="fas fa-trash"></i></a>
		</td>
	</tr>
<?php
	foreach($team->getChildren() as $child){
		$this->printTeamFunction($child);
	}
	}
}
