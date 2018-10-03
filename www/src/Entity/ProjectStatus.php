<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProjectStatusRepository")
 */
class ProjectStatus
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Team", inversedBy="projectStatuses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $team;

    /**
     * @ORM\Column(type="integer")
     */
    private $statusOrder;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Project", mappedBy="projectStatus")
     */
    private $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
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

    public function getTeam()
    {
        return $this->team;
    }

    public function setTeam(?Team $team)
    {
        $this->team = $team;

        return $this;
    }

    public function getStatusOrder()
    {
        return $this->statusOrder;
    }

    public function setStatusOrder(int $statusOrder)
    {
        $this->statusOrder = $statusOrder;

        return $this;
    }

    /**
     * @return Collection|Project[]
     */
    public function getProjects()
    {
        return $this->projects;
    }

    public function addProject(Project $project)
    {
        if (!$this->projects->contains($project)) {
            $this->projects[] = $project;
            $project->setProjectStatus($this);
        }

        return $this;
    }

    public function removeProject(Project $project)
    {
        if ($this->projects->contains($project)) {
            $this->projects->removeElement($project);
            // set the owning side to null (unless already changed)
            if ($project->getProjectStatus() === $this) {
                $project->setProjectStatus(null);
            }
        }

        return $this;
    }
}
