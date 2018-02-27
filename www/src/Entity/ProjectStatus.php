<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProjectStatusRepository")
 */
class ProjectStatus
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
    private $statusname;

	/**
     * @ORM\Column(type="integer")
     */
	private $order;

	public function getId(){         
		return $this->id;
	}

	public function getStatusname(){
		return $this->statusname;
	}

	public function setStatusname($statusname){
		$this->statusname=$statusname;
	}

	public function getOrder(){
		return $this->order;
	}

	public function setOrder($order){
		$this->order=$order;
	}

}
