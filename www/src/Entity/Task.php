<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TaskRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class Task
{
	/**
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 * @ORM\Column(type="integer")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string")
	 */
	private $name;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="tasks")
	 * @ORM\JoinColumn(nullable=true)
	 */
	private $project;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="tasks")
	 * @ORM\JoinColumn(nullable=true)
	 */
	private $assignedTo;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $done=false;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $closed=false;

	/**
	 * @ORM\Column(type="string", length=1000, nullable=true)
	 */
	private $comments;

	/**
	 * @ORM\Column(type="decimal", precision=7, scale=2, nullable=true)
	 */
	private $nbDays;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Planning", mappedBy="task", orphanRemoval=true)
	 */
	private $plannings;

	public function __construct() {
		$this->plannings = new ArrayCollection();
	}

	public function getId(){
		return $this->id;
	}

	public function getName(){
		return $this->name;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getProject(){
		return $this->project;
	}

	public function setProject($project){
		$this->project = $project;
	}

	public function getAssignedTo(){
		return $this->assignedTo;
	}

	public function setAssignedTo($assignedTo){
		$this->assignedTo = $assignedTo;
	}

	public function isDone(){
		return $this->done;
	}

	public function setDone($isDone){
		$this->done = $isDone?true:false;
	}

	public function isClosed(){
		return $this->closed;
	}

	public function setClosed($isClosed){
		$this->closed = $isClosed?true:false;
	}

	public function getComments(){
		return $this->comments;
	}

	public function setComments($comments){
		$this->comments = $comments;
	}

	public function getNbDays(){
		return $this->nbDays;
	}

	public function setNbDays($nbDays){
		if($nbDays < 0) $nbDays=0;
		$this->nbDays = $nbDays;
	}

	/**
	 * @return Collection|Planning[]
	 */
	public function getPlannings()
	{
		return $this->plannings;
	}

	public function __toString(){
		return $this->name;
	}
}
