<?php

namespace Ecortex\ProductManagerBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * RangePriceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RangePriceRepository extends EntityRepository
{
    public function getByProductRefProviderIdMinMax($productRef, $providerId, $min, $max)
    {
        $q = $this->createQueryBuilder('r')
            ->where('r.min = :min')
            ->setParameter('min', $min)
            ->andWhere('r.max = :max')
            ->setParameter('max', $max)
            ->join('r.product', 'p')
            ->andWhere('p.ref = :ref')
            ->setParameter('ref', $productRef)
            ->join('p.provider', 'pro')
            ->andWhere('pro.id = :proId')
            ->setParameter('proId', $providerId)
            ->leftJoin('r.productOptions', 'o')
            ->addSelect('o')
            ->leftJoin('r.tags', 't')
            ->addSelect('t')
            ->getQuery();

        $result = $q->getSingleResult();

        return $result;
    }

    public function getByIdWithTags($id)
    {
        $q = $this->createQueryBuilder('r')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->leftJoin('r.tags', 't')
            ->addSelect('t')
            ->getQuery();

        $result = $q->getSingleResult();

        return $result;
    }
}
