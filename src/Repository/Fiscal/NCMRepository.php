<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NCM;

/**
 * Repository para a entidade NCM.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class NCMRepository extends FilterRepository
{

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return NCM::class;
    }


    public function findByNCM($ncm)
    {
        $ql = "SELECT ncm FROM CrosierSource\CrosierLibRadxBundle\Entity\NCM ncm WHERE ncm.codigo = :ncm";
        $query = $this->getEntityManager()->createQuery($ql);
        $query->setParameters([
            'ncm' => $ncm
        ]);

        $results = $query->getResult();

        if (count($results) > 1) {
            throw new \Exception('Mais de um NCM encontrado para [' . $ncm . ']');
        }

        return count($results) === 1 ? $results[0] : null;
    }
}
