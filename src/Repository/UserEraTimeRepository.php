<?php

namespace App\Repository;

use App\Entity\UserEraTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserEraTime>
 */
class UserEraTimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserEraTime::class);
    }

    public function findAllCatalog(string $search = null): object 
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->leftJoin('u.superior', 's')
            ->leftJoin('u.superior2', 's2')
            ->leftJoin('u.superior3', 's3')
            ->andWhere('u.active = true')
            ->orderBy('u.name', 'asc')
        ;

        if ($search) {
            $queryBuilder->andWhere('LOWER(u.name) LIKE LOWER(:val)')
                ->orWhere('LOWER(u.firstName) LIKE LOWER(:val)')
                ->orWhere('LOWER(u.email) LIKE LOWER(:val)')
                ->orWhere('LOWER(u.login) LIKE LOWER(:val)')
                ->orWhere('LOWER(u.phone) LIKE LOWER(:val)')
                ->orWhere('LOWER(s.firstName )LIKE LOWER(:val)')
                ->orWhere('LOWER(s.name) LIKE LOWER(:val)')
                ->orWhere('LOWER(s2.firstName )LIKE LOWER(:val)')
                ->orWhere('LOWER(s2.name) LIKE LOWER(:val)')
                ->orWhere('LOWER(s3.firstName )LIKE LOWER(:val)')
                ->orWhere('LOWER(s3.name) LIKE LOWER(:val)')
                ->orWhere('LOWER(c.name) LIKE LOWER(:val)')
                ->andWhere('u.active = true')
                ->setParameter('val', '%'.$search.'%')
            ;
        }

        return $queryBuilder->getQuery();
    }

    public function findAllSuperior(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('CONTAINS(TO_JSONB(u.category), :category) = TRUE')
            ->orWhere('u.firstName = :name')
            ->andWhere('u.active = true')
            ->setParameter('category', '["Supérieur hiérarchique"]')
            ->setParameter('name', 'Mario')
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    //    /**
    //     * @return UserEraTime[] Returns an array of UserEraTime objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?UserEraTime
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
