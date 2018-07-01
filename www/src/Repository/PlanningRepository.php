<?php

namespace App\Repository;

use App\Entity\Planning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Planning|null find($id, $lockMode = null, $lockVersion = null)
 * @method Planning|null findOneBy(array $criteria, array $orderBy = null)
 * @method Planning[]    findAll()
 * @method Planning[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlanningRepository extends ServiceEntityRepository
{
	public function __construct(RegistryInterface $registry)
	{
		parent::__construct($registry, Planning::class);
	}

	public function findActiveBySlice($day,$hour,$userId=0){
		if($hour != "pm") $hour = "am";
		$qb = $this->createQueryBuilder('p')
			 ->where('MONTH(p.startDate) = :month')
			 ->andWhere('p.startDate <= :date');

		if($userId != 0){
			$qb->andWhere('p.user = :userid')->setParameter('userid',$userId);
		}

		$qb->setParameter('month',$day->format('m'))
	 ->setParameter('date',$day);
		$result = $qb->getQuery()->getResult();

		foreach($result as $resultKey => $resultPlanning){
			//How many days between $day and planning startDate
			$difference = $day->diff($resultPlanning->getStartDate())->format("%a");

			//Remove weekends from this count
			$interval = \DateInterval::createFromDateString('1 day');
			$period = new \DatePeriod($resultPlanning->getStartDate(), $interval, $day);
			foreach ($period as $dt) {
				if($dt->format("N") > 5) $difference--;
			}

			$diffSlices = $difference*2;
			if($hour == "pm") $diffSlices++;

			if($resultPlanning->getStartDate() == $day){
				if($resultPlanning->getStartHour() == "pm" && $hour == "am")
					unset($result[$resultKey]);
			}elseif($diffSlices >= $resultPlanning->getNbSlices()){
				unset($result[$resultKey]);
			}
		}

		return $result;
	}

	public function findActiveByDay($day,$userId=0){
		$joined = array(
			"plannings"=>array(),
			"planned"=>array()
		);
		$morning = $this->findActiveBySlice($day,"am",$userId);
		$afternoon = $this->findActiveBySlice($day,"pm",$userId);
		
		foreach($morning as $planning){
			$joined['plannings'][$planning->getId()] = $planning;
			$joined['planned'][$planning->getId()] = 0.5;
		}

		foreach($afternoon as $planning){
			if(isset($joined['plannings'][$planning->getId()])){
				$joined['planned'][$planning->getId()] = 1;
			}
			else
			{
				$joined['plannings'][$planning->getId()] = $planning;
				$joined['planned'][$planning->getId()] = 0.5;
			}
		}

		return $joined;
	}

	/*
	public function findBySomething($value)
	{
		return $this->createQueryBuilder('p')
			->where('p.something = :value')->setParameter('value', $value)
			->orderBy('p.id', 'ASC')
			->setMaxResults(10)
			->getQuery()
			->getResult()
		;
	}
	 */
}
