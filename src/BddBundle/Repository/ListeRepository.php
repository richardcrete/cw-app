<?php

namespace BddBundle\Repository;

use BddBundle\Entity\Liste;
use BddBundle\Entity\User;
use Doctrine\ORM\NonUniqueResultException;

/**
 * ListeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ListeRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @return int|mixed
     *
     * @todo do not count lists with less than 3 items
     */
    public function countTops()
    {
        try {
            return $this->getEntityManager()
                ->createQueryBuilder()
                ->select('count(1)')
                ->from('BddBundle:Liste', 'l')
                ->join('l.listeCoasters', 'lc')
                ->where('l.main = 1')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function findAllTops()
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('l')
            ->addSelect('COUNT(l.id) as HIDDEN nb')
            ->from('BddBundle:Liste', 'l')
            ->join('l.listeCoasters', 'lc')
            ->where('l.main = 1')
            ->groupBy('l.id')
            ->having('nb > 2')
            ->orderBy('l.updatedAt', 'desc')
            ->getQuery();
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function findAllCustomLists()
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('l')
            ->addSelect('COUNT(l.id) as HIDDEN nb')
            ->from('BddBundle:Liste', 'l')
            ->join('l.listeCoasters', 'lc')
            ->where('l.main = 0')
            ->groupBy('l.id')
            ->having('nb > 2')
            ->orderBy('l.updatedAt', 'desc')
            ->getQuery();
    }

    /**
     * Return all lists for a user
     *
     * @param User $user
     * @return mixed
     */
    public function findAllByUser(User $user)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('l')
            ->from('BddBundle:Liste', 'l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.updatedAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Liste $liste
     * @return mixed
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getListeWithData(Liste $liste)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('l', 'lc', 'c', 'm', 'p', 'co', 't')
            ->from('BddBundle:Liste', 'l')
            ->leftJoin('l.listeCoasters', 'lc')
            ->leftJoin('lc.coaster', 'c')
            ->leftJoin('c.park', 'p')
            ->leftJoin('p.country', 'co')
            ->leftJoin('c.manufacturer', 'm')
            ->leftJoin('c.types', 't')
            ->where('l = :liste')
            ->setParameter('liste', $liste)
            ->getQuery()
            ->getSingleResult();
    }
}
