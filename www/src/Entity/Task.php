<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TaskRepository")
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

}
