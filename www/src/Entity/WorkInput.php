<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WorkInputRepository")
 */
class WorkInput
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="workInputs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="workInputs")
	 * @ORM\JoinColumn(nullable=true)
     */
    private $project;

    /**
     * @ORM\Column(type="date")
     */
    private $weekStart;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private $mon;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private $tue;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private $wed;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private $thu;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2, nullable=true)
     */
    private $fri;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $locked;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getWeekStart(): ?int
    {
        return $this->weekStart;
    }

    public function setWeekStart(\DateTime $weekStart): self
    {
        $this->weekStart = $weekStart;

        return $this;
	}

	public function getDay($day){
		switch($day){
			case 0:
				return $this->mon;
			case 1:
				return $this->tue;
			case 2:
				return $this->wed;
			case 3:
				return $this->thu;
			case 4:
				return $this->fri;
			default:
				return 0;
		}
	}

    public function getMon()
    {
        return $this->mon;
    }

    public function setMon($mon): self
    {
        $this->mon = $mon;

        return $this;
    }

    public function getTue()
    {
        return $this->tue;
    }

    public function setTue($tue): self
    {
        $this->tue = $tue;

        return $this;
    }

    public function getWed()
    {
        return $this->wed;
    }

    public function setWed($wed): self
    {
        $this->wed = $wed;

        return $this;
	}

    public function getThu()
    {
        return $this->thu;
    }

    public function setThu($thu): self
    {
        $this->thu = $thu;

        return $this;
	}

    public function getFri()
    {
        return $this->fri;
    }

    public function setFri($fri): self
    {
        $this->fri = $fri;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(?bool $locked): self
    {
        $this->locked = $locked;

        return $this;
    }
    // add your own fields
}
