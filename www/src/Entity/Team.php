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
     * @ORM\OneToMany(targetEntity="Team", mappedBy="parent", orphanRemoval=false)
	 */
	private $children;

	/**
	 * @ORM\ManyToOne(targetEntity="Team", inversedBy="children")
	 * @ORM\JoinColumn(nullable=true,onDelete="SET NULL")
     */
	private $parent;

	public function __construct() {
        $this->users = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
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

	public function getChildren(){
		return $this->children;
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

	public function __toString(){
		return $this->name;
	}
}
