<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

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

}
