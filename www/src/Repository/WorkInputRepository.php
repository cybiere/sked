<?php

namespace App\Repository;

use App\Entity\WorkInput;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method WorkInput|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkInput|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkInput[]    findAll()
 * @method WorkInput[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkInputRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, WorkInput::class);
    }

    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('w')
            ->where('w.something = :value')->setParameter('value', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
