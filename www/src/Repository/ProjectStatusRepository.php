<?php

namespace App\Repository;

use App\Entity\ProjectStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ProjectStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectStatus[]    findAll()
 * @method ProjectStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectStatusRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ProjectStatus::class);
    }

//    /**
//     * @return ProjectStatus[] Returns an array of ProjectStatus objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
	 */

	public function findByTeam($team)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.team = :val')
            ->setParameter('val', $team)
            ->orderBy('p.statusOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;
	}

    public function findMaxOrder($team)
    {
		$result = $this->createQueryBuilder('p')
			->select('MAX(p.statusOrder) AS max_order')
            ->andWhere('p.team = :val')
            ->setParameter('val', $team)
            ->groupBy('p.team')
            ->getQuery()
            ->getResult()
		;
		if($result == []){
			return 0;
		}

		return $result[0]['max_order'];
	}

    public function findInTeamByOrder($team,$order)
	{
		$result = $this->createQueryBuilder('p')
            ->andWhere('p.team = :team')
            ->andWhere('p.statusOrder = :order')
            ->setParameter('team', $team)
            ->setParameter('order', $order)
            ->getQuery()
            ->getResult();
		return $result == NULL?NULL:$result[0];
    }

	
    /*
    public function findOneBySomeField($value): ?ProjectStatus
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
