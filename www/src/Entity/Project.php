<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\Column(type="integer", nullable=true)
     */
	private $nbDays;

	/**
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
	private $comments;

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
		$this->status = $status;
	}

	public function getNbDays(){
		return $this->nbDays;
	}

	public function setNbDays($nbDays){
		$this->nbDays = $nbDays;
	}

	public function getComments(){
		return $this->comments;
	}

	public function setComments($comments){
		$this->comments = $comments;
	}
}
