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
     * @ORM\Column(type="integer")
     */
	private $status;

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
     * @ORM\OneToMany(targetEntity="App\Entity\Planning", mappedBy="project", orphanRemoval=true)
	 */
	private $plannings;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="managedProjects")
	 * @ORM\JoinColumn(nullable=true)
     */
	private $projectManager;


    public function __construct()
    {
        $this->plannings = new ArrayCollection();
    }

	public function __toString(){
		return $this->name." (".$this->reference.")";
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

	public function getStatus(){
		return $this->status;
	}

	public function setStatus($status){
		if($status>7) $status = 7;
		if($status<0) $status = 0;
		$this->status = $status;
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

	/**
     * @return Collection|Planning[]
     */
    public function getPlannings()
    {
        return $this->plannings;
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

}
