<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


/**
 * @ORM\Entity(repositoryClass="App\Repository\ProjectRepository")
 */
class Project
{
	/**
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 * @ORM\Column(type="integer")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=10)
	 */
	private $reference;

	/**
	 * @ORM\Column(type="string", length=200)
	 */
	private $name;

	/**
	 * @ORM\Column(type="string", length=200)
	 */
	private $client;

	/**
	 * @ORM\Column(type="decimal", precision=7, scale=2, nullable=true)
	 */
	private $nbDays;

	/**
	 * @ORM\Column(type="string", length=1000, nullable=true)
	 */
	private $comments;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $billable=true;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $archived=true;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Planning", mappedBy="project", orphanRemoval=true)
	 */
	private $plannings;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Task", mappedBy="project", orphanRemoval=true)
	 */
	private $tasks;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="managedProjects")
	 * @ORM\JoinColumn(nullable=true,onDelete="SET NULL")
	 */
	private $projectManager;

	/**
	 * @ORM\ManyToOne(targetEntity="Team", inversedBy="projects")
	 */
	private $team;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\ProjectStatus", inversedBy="projects")
	 */
	private $projectStatus;


	public function __construct()
	{
		$this->plannings = new ArrayCollection();
		$this->tasks = new ArrayCollection();
	}

	public function getId(){
		return $this->id;
	}

	public function getReference(){
		return $this->reference;
	}

	public function setReference($reference){
		$this->reference = $reference;
	}

	public function getName(){
		return $this->name;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getClient(){
		return $this->client;
	}

	public function setClient($client){
		$this->client = $client;
	}

	public function getNbDays(){
		return $this->nbDays;
	}

	public function setNbDays($nbDays){
		if($nbDays < 0) $nbDays=0;
		$this->nbDays = $nbDays;
	}

	public function getComments(){
		return $this->comments;
	}

	public function setComments($comments){
		$this->comments = $comments;
	}

	public function isBillable(){
		return $this->billable;
	}

	public function setBillable($billable){
		$this->billable = $billable?true:false;
	}

	public function isArchived(){
		return $this->archived;
	}

	public function setArchived($archived){
		$this->archived = $archived?true:false;
	}

	/**
	 * @return Collection|Planning[]
	 */
	public function getPlannings()
	{
		return $this->plannings;
	}

	/**
	 * @return Collection|Task[]
	 */
	public function getTasks(){
		return $this->tasks;
	}

	public function getPlannedDays(){
		$slicesPlanned = 0;
		foreach($this->plannings as $planning){
			$slicesPlanned += $planning->getNbSlices();
		}	
		return $slicesPlanned/2;
	}

	public function getProjectManager(){
		return $this->projectManager;
	}

	public function setProjectManager($projectManager){
		$this->projectManager = $projectManager;
	}

	public function getTeam(){
		return $this->team;
	}

	public function setTeam($team){
		$this->team = $team;
	}

	public function __toString(){
		return $this->client.' - '.$this->name;
	}

	public function getProjectStatus(): ?ProjectStatus
	{
		return $this->projectStatus;
	}

	public function setProjectStatus(?ProjectStatus $projectStatus): self
	{
		$this->projectStatus = $projectStatus;
		return $this;
	}
}
