<?php

namespace App\Repository;

use App\Entity\HealthProfessional;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HealthProfessional|null find($id, $lockMode = null, $lockVersion = null)
 * @method HealthProfessional|null findOneBy(array $criteria, array $orderBy = null)
 * @method HealthProfessional[]    findAll()
 * @method HealthProfessional[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HealthProfessionalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthProfessional::class);
    }

    /**
     * @return HealthProfessional[] Returns an array of HealthProfessional objects
     */
    public function getHealthProfesisonals()
    {
        return $this->createQueryBuilder('h')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param HealthProfessional $healthProfessional
     * @return HealthProfessional
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function store(HealthProfessional $healthProfessional): HealthProfessional
    {
        $this->_em->persist($healthProfessional);
        $this->_em->flush();
        return $healthProfessional;
    }

    public function getHealthProfessionalByld($id): ?HealthProfessional
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     *
     * @param int $id
     */
    public function delete($id)
    {
        $RAW_QUERY = 'DELETE FROM health_professional_contact where id = ' . $id;
        $statement = $this->_em->getConnection()->prepare($RAW_QUERY);
        $statement->execute();
    }
}
