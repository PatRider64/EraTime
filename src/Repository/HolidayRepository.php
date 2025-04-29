<?php

namespace App\Repository;

use App\Entity\Holiday;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Holiday>
 */
class HolidayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Holiday::class);
    }

    public function findAllCatalog(string $search = null): object
    {
        $queryBuilder = $this->createQueryBuilder('h')
            ->join('h.users', 'u')
            ->join('h.type', 't')
            ->andWhere('t.id != 8')
            ->andWhere('u.active = true')
            ->orderBy('h.dateDemande', 'DESC')
        ;

        if ($search) {
            $queryBuilder->andWhere('LOWER(u.name) LIKE LOWER(:val)')
                ->orWhere('LOWER(u.firstName) LIKE LOWER(:val)')
                ->orWhere('LOWER(t.name) LIKE LOWER(:val)')
                ->orWhere('LOWER(h.observation) LIKE LOWER(:val)')
                ->orWhere('LOWER(h.status) LIKE LOWER(:val)')
                ->orWhere('LOWER(c.name) LIKE LOWER(:val)')
                ->andWhere('t.id != 8')
                ->setParameter('val', '%'.$search.'%')
            ;
        }

        return $queryBuilder->getQuery();
    }

//    /**
//     * @return Holiday[] Returns an array of Holiday objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('h.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Holiday
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
