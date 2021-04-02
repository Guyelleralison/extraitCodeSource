<?php

namespace App\Repository;

use App\Entity\PatientAccountLinked;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PatientAccountLinked|null find($id, $lockMode = null, $lockVersion = null)
 * @method PatientAccountLinked|null findOneBy(array $criteria, array $orderBy = null)
 * @method PatientAccountLinked[]    findAll()
 * @method PatientAccountLinked[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PatientAccountLinkedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatientAccountLinked::class);
    }

    /**
     * @param PatientAccountLinked $PatientAccountLinked
     * @return PatientAccountLinked
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function store(PatientAccountLinked $patient): PatientAccountLinked
    {
        $this->_em->persist($patient);
        $this->_em->flush();
        return $patient;
    }


    // /**
    //  * @return PatientAccountLinked[] Returns an array of PatientAccountLinked objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /**
    * @return PatientAccountLinked[] Returns an array of PatientAccountLinked objects
    */
    public function findAccountLinkedByPatient($patient)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.parent_patient = :patient or f.patientLinked = :patient')
            ->setParameter('patient', $patient)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PatientAccountLinked[] Returns an array of PatientAccountLinked objects
     */
    public function findAccountLinkedByTwoPatient($patientOne, $patientTwo)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.parent_patient = :patientOne and f.patientLinked = :patientTwo
            or f.parent_patient = :patientTwo and f.patientLinked = :patientOne')
            ->setParameter('patientOne', $patientOne)
            ->setParameter('patientTwo', $patientTwo)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
