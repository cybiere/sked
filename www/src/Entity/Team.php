<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TeamRepository")
 */
class Team
{
	/**
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 * @ORM\Column(type="integer")
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $name;

	/**
	 * @ORM\ManyToMany(targetEntity="User", inversedBy="teams")
	 * @ORM\JoinTable(name="users_teams")
	 */
	private $users;

	/**
	 * @ORM\ManyToMany(targetEntity="User", inversedBy="managedTeams")
	 * @ORM\JoinTable(name="managers_teams")
	 */
	private $managers;

	/**
	 * @ORM\OneToMany(targetEntity="Team", mappedBy="parent", orphanRemoval=false)
	 */
	private $children;

	/**
	 * @ORM\ManyToOne(targetEntity="Team", inversedBy="children")
	 * @ORM\JoinColumn(nullable=true,onDelete="SET NULL")
	 */
	private $parent;

	/**
	 * @ORM\OneToMany(targetEntity="Project", mappedBy="team")
	 */
	private $projects;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\ProjectStatus", mappedBy="team", orphanRemoval=true)
	 */
	private $projectStatuses;

	public function __construct() {
		$this->users = new ArrayCollection();
		$this->children = new ArrayCollection();
		$this->projects = new ArrayCollection();
		$this->projectStatuses = new ArrayCollection();
	}

	public function getId()
	{
		return $this->id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName(string $name)
	{
		$this->name = $name;

		return $this;
	}

	public function getUsers(){
		return $this->users;
	}

	public function addUser(User $user){
		$this->users[] = $user;
		return $this;
	}

	public function removeUser(User $user){
		if (!$this->users->contains($user)) {
			return $this;
		}
		$this->users->removeElement($user);
		return $this;
	}

	public function getManagers(){
		return $this->managers;
	}

	public function addManager(User $manager){
		$this->managers[] = $manager;
		return $this;
	}

	public function removeManager(User $manager){
		if (!$this->managers->contains($manager)) {
			return $this;
		}
		$this->managers->removeElement($manager);
		return $this;
	}

	public function getChildren(){
		return $this->children;
	}

	public function getRecurChildren(){
		$children = [];
		foreach($this->children as $child){
			$children[] = $child;
			$children = array_merge($children,$child->getRecurChildren());
		}
		return array_unique($children);
	}

	public function addChild($child){
		$this->children[] = $child;
		return $this;
	}

	public function getParent(){
		return $this->parent;
	}

	public function setParent(Team $parent){
		$this->parent = $parent;
		return $this;
	}

	public function getLevel(){
		if($this->parent == NULL){
			return 0;
		}
		return $this->parent->getLevel() + 1;
	}

	public function getProjects(){
		return $this->projects;
	}

	public function __toString(){
		return $this->name;
	}

	public function canAdmin(User $user){
		if(in_array($user,$this->managers->toArray()))
			return true;
		if($this->parent == NULL) return false;
		return $this->parent->canAdmin($user);
	}

	/**
	 * @return Collection|ProjectStatus[]
	 */
	public function getProjectStatuses(): Collection
	{
		return $this->projectStatuses;
	}

	public function addProjectStatus(ProjectStatus $projectStatus)
	{
		if (!$this->projectStatuses->contains($projectStatus)) {
			$this->projectStatuses[] = $projectStatus;
			$projectStatus->setTeam($this);
		}
		return $this;
	}

	public function removeProjectStatus(ProjectStatus $projectStatus)
	{
		if ($this->projectStatuses->contains($projectStatus)) {
			$this->projectStatuses->removeElement($projectStatus);
			if ($projectStatus->getTeam() === $this) {
				$projectStatus->setTeam(null);
			}
		}
		return $this;
	}
}
