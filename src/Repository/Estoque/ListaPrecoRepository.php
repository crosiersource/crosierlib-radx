<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ListaPreco;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class ListaPrecoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return ListaPreco::class;
    }

    /**
     * Retorna todas as listas de preços em formato select2js
     * @return array
     */
    public function findAllSelect2JS(): array
    {
        $sql = 'SELECT * FROM est_lista_preco ORDER BY descricao';
        $rs = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
        $results = [
            [
                'id' => 0,
                'text' => '...',
            ]
        ];
        foreach ($rs as $r) {
            $results[] = [
                'id' => $r['id'],
                'text' => $r['descricao'],
            ];
        }
        return $results;
    }

}
