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
     * @ORM\Column(type="date")
     */
	private $endDate;
	/**
     * @ORM\Column(type="string", length=2)
     */
	private $endHour;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="plannings")
     * @ORM\JoinColumn(nullable=true)
     */
	private $project;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="plannings")
     * @ORM\JoinColumn(nullable=true)
     */
	private $user;

	public function getId(){
		return $this->id;
	}

	public function getStartDate(){
		return $this->startDate();
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

	public function getEndDate(){
		return $this->endDate();
	}

	public function setEndDate($endDate){
		if(isset($this->startDate) && $this->startDate > $endDate){
			$this->endDate = $this->startDate;
			$this->setStartDate($endDate);
		}else{
			$this->endDate = $endDate;
		}
	}

	public function getEndHour(){
		return $this->endHour;
	}

	public function setEndHour($endHour){
		if($endHour != "pm") $endHour = "am";
		$this->endHour = $endHour;
	}

	public function getProject(){
		return $this->project();
	}

	public function setProject($project){
		$this->project = $project;
	}

	public function getUser(){
		return $this->user();
	}

	public function setUser($user){
		$this->user = $user;
	}
}
