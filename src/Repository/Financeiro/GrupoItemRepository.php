<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem;

/**
 * Repository para a entidade GrupoItem.
 *
 * @author Carlos Eduardo Pauluk
 */
class GrupoItemRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return GrupoItem::class;
    }

    public function findByMesAno(\DateTime $mesAno)
    {
        $dtIni = \DateTime::createFromFormat('Y-m-d', $mesAno->format('Y-m-') . '01')->setTime(0, 0, 0, 0);
        $dtFim = \DateTime::createFromFormat('Y-m-d', $mesAno->format('Y-m-t'))->setTime(23, 59, 59, 999999);

        $ql = "SELECT e FROM CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem e WHERE e.dtVencto BETWEEN :dtIni AND :dtFim";

        $qry = $this->getEntityManager()->createQuery($ql);
        $qry->setParameter('dtIni', $dtIni);
        $qry->setParameter('dtFim', $dtFim);
        return $qry->getResult();
    }
}
