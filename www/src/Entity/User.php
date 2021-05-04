<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;


	/**
     * @ORM\Column(type="string", length=100)
     */
    private $username;

	/**
     * @ORM\Column(type="string", length=100)
     */
    private $fullname;

	/**
     * @ORM\Column(type="string", length=200)
     */
    private $email;

	/**
     * @ORM\Column(type="boolean")
     */
	private $isResource=true;

	/**
     * @ORM\Column(type="boolean")
     */
	private $isAdmin=false;

	/**
     * @ORM\Column(name="`order`", type="integer")
     */
	private $order;

	/**
     * @ORM\OneToMany(targetEntity="App\Entity\Project", mappedBy="projectManager", orphanRemoval=false)
	 */
	private $managedProjects;

	/**
     * @ORM\OneToMany(targetEntity="App\Entity\Planning", mappedBy="user", orphanRemoval=true)
	 */
	private $plannings;

	/**
     * @ORM\OneToMany(targetEntity="App\Entity\Task", mappedBy="assignedTo", orphanRemoval=true)
	 */
	private $tasks;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Team", inversedBy="users")
     */
    private $team;

	/**
     * @ORM\ManyToMany(targetEntity="Team", mappedBy="managers")
     */
    private $managedTeams;

    public function __construct()
    {
        $this->managedProjects = new ArrayCollection();
        $this->plannings = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

	public function __toString(){
		return $this->fullname;
	}

	public function getId(){
		return $this->id;
	}

	public function getUsername(){
		return $this->username;
	}

	public function setUsername($username){
		$this->username=$username;
	}

	public function getFullname(){
		return $this->fullname;
	}

	public function setFullname($fullname){
		$this->fullname=$fullname;
	}

	public function getEmail(){
		return $this->email;
	}

	public function setEmail($email){
		$this->email=$email;
	}

	public function isResource(){
		return $this->isResource;
	}

	public function setIsResource($resource){
		$this->isResource = $resource?true:false;
	}

	public function isAdmin(){
		return $this->isAdmin;
	}

	public function setIsAdmin($admin){
		$this->isAdmin = $admin?true:false;
	}

	public function setOrder($value){
		$this->order = $value;
	}

	public function getOrder(){
		return $this->order;
	}

	/**
     * @return Collection|Project[]
     */
    public function getManagedProjects()
    {
		return $this->managedProjects;
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
    public function getTasks()
    {
        return $this->tasks;
    }

	/**
     * @return Team
     */
    public function getTeam()
    {
		return $this->team;
	}
    public function setTeam($team)
    {
		$this->team = $team;
		return $this;
	}

	/**
     * @return Collection|Team[]
     */
    public function getManagedTeams()
	{
		$managed = [];
		foreach($this->managedTeams as $team){
			$managed[] = $team;
			$managed = array_merge($managed,$team->getRecurChildren());
		}
		return array_unique($managed);
	}

	public function canAdmin($target){
		if($this->isAdmin){ return true; }
		if(is_a($target,Project::class)){
			$pm = $target->getProjectManager();
			if($pm != NULL && $pm == $this){ 
				return true; 
			}
			if(($team = $target->getTeam()) == NULL){
				return false;
			}
			return $this->canAdmin($team);
		}
		if(is_a($target,Planning::class)){
			if($target->getProject() == NULL) return $this->canAdmin($target->getUser());
			return $this->canAdmin($target->getProject());
		}
		if(is_a($target,User::class)){
			if($target->getTeam() == null) return false;
			if($target->getTeam()->canAdmin($this)) return true;
			return false;
		}
		if(is_a($target,Team::class)){
			return $target->canAdmin($this);
		}
		if(is_a($target,Task::class)){
			if(($project = $target->getProject()) == NULL){ return false; }
			return $this->canAdmin($project);
		}
		return false;
	}
}
