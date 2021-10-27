<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Unidade;

/**
 * Repository para a entidade Unidade.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class UnidadeRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Unidade::class;
    }

    public function findUnidadesAtuaisSelect2JS(): array
    {
        $sql = 'SELECT * FROM est_unidade ORDER BY label';
        $rs = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
        $results = [
            [
                'id' => 0,
                'text' => '...',
                'casas_decimais' => 0
            ]
        ];
        foreach ($rs as $r) {
            $results[] = [
                'id' => $r['id'],
                'text' => $r['label'],
                'casas_decimais' => $r['casas_decimais']
            ];
        }
        return $results;
    }
}
