<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Vendas;


use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\PlanoPagto;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 * Repository para a entidade PlanoPagto.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class PlanoPagtoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return PlanoPagto::class;
    }

    public function findByDescricao($descricao)
    {
        $ql = "SELECT pp FROM CrosierSource\CrosierLibRadxBundle\Entity\Vendas\PlanoPagto pp WHERE pp.descricao = :descricao";
        $query = $this->getEntityManager()->createQuery($ql);
        $query->setParameters(array(
            'descricao' => $descricao
        ));

        $results = $query->getResult();

        if (count($results) > 1) {
            throw new \Exception('Mais de um plano de pagto encontrado para [' . $descricao . ']');
        }

        return count($results) == 1 ? $results[0] : null;
    }

    public function findAtuaisSelect2JS(): array
    {
        $sql = 'SELECT * FROM ven_plano_pagto WHERE ativo IS TRUE ORDER BY codigo';
        $rs = $this->getEntityManager()->getConnection()->fetchAll($sql);
        $results = [
            [
                'id' => 0,
                'text' => '...'
            ]
        ];
        foreach ($rs as $r) {
            $results[] = [
                'id' => $r['id'],
                'text' => $r['codigo'] . ' - ' . $r['descricao'],
            ];
        }
        return $results;
    }
}
