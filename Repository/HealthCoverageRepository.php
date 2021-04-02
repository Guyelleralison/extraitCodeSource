<?php

namespace App\Repository;

use App\Entity\HealthCoverage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HealthCoverage|null find($id, $lockMode = null, $lockVersion = null)
 * @method HealthCoverage|null findOneBy(array $criteria, array $orderBy = null)
 * @method HealthCoverage[]    findAll()
 * @method HealthCoverage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HealthCoverageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthCoverage::class);
    }

    // /**
    //  * @return HealthCoverage[] Returns an array of HealthCoverage objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?HealthCoverage
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @param HealthCoverage $healthProfessional
     * @return HealthCoverage
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function store(HealthCoverage $healthCoverage): HealthCoverage
    {
        $this->_em->persist($healthCoverage);
        $this->_em->flush();
        return $healthCoverage;
    }

    /**
     *
     * @param int $id
     */
    public function delete($id)
    {
        $RAW_QUERY = 'DELETE FROM health_coverage where id = ' . $id;
        $statement = $this->_em->getConnection()->prepare($RAW_QUERY);
        $statement->execute();
    }
}
