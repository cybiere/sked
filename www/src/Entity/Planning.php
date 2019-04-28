<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlanningRepository")
 */
class Planning
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

	/**
     * @ORM\Column(type="date")
     */
	private $startDate;

	/**
     * @ORM\Column(type="string", length=2)
     */
	private $startHour;

	/**
     * @ORM\Column(type="integer")
     */
	private $nbSlices;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="plannings")
	 * @ORM\JoinColumn(nullable=true)
     */
	private $project;

	/**
     * @ORM\Column(type="boolean")
     */
	private $meeting=false;

	/**
     * @ORM\Column(type="boolean")
     */
	private $confirmed=false;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="plannings")
	 * @ORM\JoinColumn(nullable=true)
     */
	private $user;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Task", inversedBy="plannings")
	 * @ORM\JoinColumn(nullable=true)
     */
	private $task;

	public $offset;

	public function getId(){
		return $this->id;
	}

	public function getStartDate(){
		return $this->startDate;
	}

	public function setStartDate($startDate){
		if(isset($this->endDate) && $this->endDate < $startDate){
			$this->startDate = $this->endDate;
			$this->setEndDate($startDate);
		}else{
			$this->startDate = $startDate;
		}
	}

	public function getStartHour(){
		return $this->startHour;
	}

	public function setStartHour($startHour){
		if($startHour != "pm") $startHour = "am";
		$this->startHour = $startHour;
	}

	public function getNbSlices(){
		return $this->nbSlices;
	}

	public function setNbSlices($nbSlices){
		$this->nbSlices = $nbSlices > 0?$nbSlices:1;
	}

	public function isMeeting(){
		return $this->meeting;
	}

	public function setMeeting($isMeeting){
		$this->meeting = $isMeeting?true:false;
	}

	public function isConfirmed(){
		return $this->confirmed;
	}

	public function setConfirmed($isConfirmed){
		$this->confirmed = $isConfirmed?true:false;
	}

	public function getProject(){
		return $this->project;
	}

	public function setProject($project){
		$this->project = $project;
	}

	public function getUser(){
		return $this->user;
	}

	public function setUser($user){
		$this->user = $user;
	}

	public function getTask(){
		return $this->task;
	}

	public function setTask($task){
		$this->task = $task;
	}

}
