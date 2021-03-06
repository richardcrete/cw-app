<?php

namespace App\Repository;

use App\Entity\Coaster;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

/**
 * TopCoasterRepository
 */
class TopCoasterRepository extends EntityRepository
{
    /**
     * @param Coaster $coaster
     * @return mixed|null
     */
    public function countForCoaster(Coaster $coaster)
    {
        try {
            return $this->getEntityManager()
                ->createQueryBuilder()
                ->select('count(1)')
                ->from('App:TopCoaster', 'l')
                ->where('l.coaster = :coaster')
                ->setParameter('coaster', $coaster)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * Count all coasters inside main tops only
     *
     * @return mixed
     */
    public function countAllInTops()
    {
        try {
            return $this->getEntityManager()
                ->createQueryBuilder()
                ->select('count(1)')
                ->from('App:TopCoaster', 'tc')
                ->join('tc.top', 't')
                ->where('t.main = 1')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * Update totalTopsIn for all coasters
     *
     * @return bool
     */
    public function updateTotalTopsIn()
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = '
            UPDATE coaster c
            JOIN (
                SELECT lc.coaster_id AS id, COUNT(1) AS nb
                FROM liste_coaster lc
                JOIN liste l ON l.id = lc.top_id
                WHERE l.main = 1
                GROUP BY lc.coaster_id
            ) c2
            ON c2.id = c.id
            SET c.total_tops_in = c2.nb
            ';

        try {
            $connection->executeQuery($sql);
        } catch (DBALException $e) {
            return false;
        }

        return true;
    }

    /**
     * Update averageTopRank for all coasters
     *
     * @param int $minTopsIn
     * @return bool
     */
    public function updateAverageTopRanks(int $minTopsIn)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = '
            UPDATE coaster c
            JOIN (
                SELECT lc.coaster_id AS id, FORMAT(AVG(position), 3) AS average
                FROM liste_coaster lc
                GROUP BY lc.coaster_id
            ) c2
            ON c2.id = c.id
            SET c.average_top_rank = c2.average
            WHERE c.total_tops_in >= :minTopsIn
            ';

        try {
            $statement = $connection->prepare($sql);
            $statement->execute(['minTopsIn' => $minTopsIn]);

            return $statement->rowCount();
        } catch (DBALException $e) {
            return false;
        }
    }
}
