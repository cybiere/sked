<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlanningRepository")
 */
class Planning
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

	/**
     * @ORM\Column(type="date")
     */
	private $startDate;

	/**
     * @ORM\Column(type="string", length=3)
     */
	private $startHour;

	/**
	 * @ORM\Column(type="decimal", precision=7, scale=2)
     */
	private $nbSlices;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="plannings")
	 * @ORM\JoinColumn(nullable=true)
     */
	private $project;

	/**
     * @ORM\Column(type="boolean")
     */
	private $meeting=false;

	/**
     * @ORM\Column(type="boolean")
     */
	private $confirmed=false;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $deliverable=false;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $meetup=false;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $capitalization=false;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $nomonitoring=false;

	/**
	 * @ORM\Column(type="string", length=1000, nullable=true)
	 */
	private $comments;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="plannings")
	 * @ORM\JoinColumn(nullable=true)
     */
	private $user;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Task", inversedBy="plannings")
	 * @ORM\JoinColumn(nullable=true)
     */
	private $task;

	public $offset;

	public function getId(){
		return $this->id;
	}

	public function getStartDate(){
		return $this->startDate;
	}

	public function getStart() {
		$data = clone $this->getStartDate();

		$hours = 8;

		if ($this->getStartHour() == "am") $hours = 8;
		if ($this->getStartHour() == "am2") $hours = 10;
		if ($this->getStartHour() == "pm") $hours = 13;
		if ($this->getStartHour() == "pm2") $hours = 15;

		$data->modify("+{$hours} hours");

		return clone $data;
	}

	public function getEnd() {
		$data = $this->getStart();

		// iterate over each quarter of day
		for ($i = 0; $i < $this->getNbSlices(); $i += 0.5) {
			// pass over weekend
			if (
				in_array(
					$data->format('N'),
					array(
						6,
						7
					)
				)
			) {
				$data->modify("+1 day");
			}

			// pass over holidays 
			if (
				in_array(
					$data->format("Y-m-d"),
					\App\Controller\CommonController::getHolidays($data->format('Y'), "Y-m-d")
				)
			) {
				$data->modify("+1 day");
			}

			// adjust hour as am/am2/pm/pm2
			if ($data->format('G') == 8) {
				$data->modify("+2 hours");
			} else if ($data->format('G') == 10) {
				$data->modify("+3 hours");
			} else if ($data->format('G') == 13) {
				$data->modify("+2 hours");
			} else if ($data->format('G') == 15) {
				$data->modify("+17 hours");
			} else if ($data->format('G') == 15) {
				$data->modify("+17 hours");
			}

			// don't encroach on next day
			if (
				$data->format('G') == 8 &&
				$i == $this->getNbSlices() - 0.5
			) {
				$data->modify("-15 hours");
			}
		}

		return $data;
	}

	public function setStartDate($startDate){
		if(isset($this->endDate) && $this->endDate < $startDate){
			$this->startDate = $this->endDate;
			$this->setEndDate($startDate);
		}else{
			$this->startDate = $startDate;
		}
	}

	public function getStartHour(){
		return $this->startHour;
	}

	public function setStartHour($startHour){
		if (! in_array(
			$startHour,
			array(
				'am',
				'am2',
				'pm',
				'pm2'
			)
		)) {
			$startHour = "am";
		}

		$this->startHour = $startHour;
	}

	public function getNbSlices(){
		return $this->nbSlices;
	}

	public function setNbSlices($nbSlices){
		$this->nbSlices = $nbSlices > 0?$nbSlices:1;
	}

	public function isMeeting(){
		return $this->meeting;
	}

	public function setMeeting($isMeeting){
		$this->meeting = $isMeeting?true:false;
	}

	public function isConfirmed(){
		return $this->confirmed;
	}

	public function setConfirmed($isConfirmed){
		$this->confirmed = $isConfirmed?true:false;
	}

	public function isDeliverable(){
		return $this->deliverable;
	}

	public function setDeliverable($deliverable){
		$this->deliverable = $deliverable?true:false;
	}

	public function isMeetup(){
		return $this->meetup;
	}

	public function setMeetup($meetup){
		$this->meetup = $meetup?true:false;
	}

	public function isCapitalization(){
		return $this->capitalization;
	}

	public function setCapitalization($capitalization){
		$this->capitalization = $capitalization?true:false;
	}

	public function isMonitoring(){
		return ! $this->nomonitoring;
	}

	public function setMonitoring($data){
		$this->nomonitoring = $data?false:true;
	}

	public function getProject(){
		return $this->project;
	}

	public function setProject($project){
		$this->project = $project;
	}

	public function getUser(){
		return $this->user;
	}

	public function setUser($user){
		$this->user = $user;
	}

	public function getTask(){
		return $this->task;
	}

	public function setTask($task){
		$this->task = $task;
	}

	public function getComments(){
		return $this->comments;
	}

	public function setComments($comments){
		$this->comments = $comments;
	}

}
