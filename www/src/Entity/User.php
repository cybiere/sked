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
     * @ORM\OneToMany(targetEntity="App\Entity\Planning", mappedBy="user", orphanRemoval=true)
	 */
	private $plannings;
 /**
  * @ORM\OneToMany(targetEntity="App\Entity\WorkInput", mappedBy="user", orphanRemoval=true)
  */
 private $workInputs;


    public function __construct()
    {
        $this->plannings = new ArrayCollection();
        $this->workInputs = new ArrayCollection();
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

	/**
     * @return Collection|Planning[]
     */
    public function getPlannings()
    {
        return $this->plannings;
    }

    /**
     * @return Collection|WorkInput[]
     */
    public function getWorkInputs(): Collection
    {
        return $this->workInputs;
    }

    public function addWorkInput(WorkInput $workInput): self
    {
        if (!$this->workInputs->contains($workInput)) {
            $this->workInputs[] = $workInput;
            $workInput->setUser($this);
        }

        return $this;
    }

    public function removeWorkInput(WorkInput $workInput): self
    {
        if ($this->workInputs->contains($workInput)) {
            $this->workInputs->removeElement($workInput);
            // set the owning side to null (unless already changed)
            if ($workInput->getUser() === $this) {
                $workInput->setUser(null);
            }
        }

        return $this;
    }
}
