<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Vendas;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\PlanoPagto;

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

    /**
     * @param $descricao
     * @return |null
     * @throws \Exception
     */
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

    /**
     * @return array
     */
    public function findAtuaisSelect2JS(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT * FROM ven_plano_pagto WHERE ativo IS TRUE ORDER BY codigo';
        $rs = $conn->fetchAll($sql);
        $results = [
            [
                'id' => 0,
                'text' => '...'
            ]
        ];

        $rCarteirasCaixas = $conn->fetchAll('SELECT * FROM fin_carteira WHERE caixa IS TRUE');
        $rCarteirasCartao = $conn->fetchAll('SELECT * FROM fin_carteira WHERE operadora_cartao_id IS NOT NULL');
        $rCarteirasBanco = $conn->fetchAll('SELECT * FROM fin_carteira WHERE banco_id IS NOT NULL OR codigo = \'99\'');

        foreach ($rs as $r) {
            $jsonData = json_decode($r['json_data'], true);

            switch ($jsonData['tipo_carteiras']) {
                case 'operadora_cartao':
                    $carteiras = $rCarteirasCartao;
                    break;
                case 'banco':
                    $carteiras = $rCarteirasBanco;
                    break;
                case 'caixa':
                default:
                    $carteiras = $rCarteirasCaixas;
            }

            switch ($jsonData['tipo_carteiras_destino'] ?? null) {
                case 'operadora_cartao':
                    $carteirasDestino = $rCarteirasCartao;
                    break;
                case 'banco':
                    $carteirasDestino = $rCarteirasBanco;
                    break;
                default:
                    $carteirasDestino = null;
            }

            $results[] = [
                'id' => $r['id'],
                'text' => $r['codigo'] . ' - ' . $r['descricao'],
                'json_data' => json_decode($r['json_data'], true),
                'carteiras' => $carteiras ?? null,
                'carteirasDestino' => $carteirasDestino ?? null
            ];
        }
        return $results;
    }

    /**
     * @param bool|null $somenteAtivos
     * @return mixed
     */
    public function arrayByCodigo(?bool $somenteAtivos = true)
    {
        $sql = 'SELECT * FROM ven_plano_pagto WHERE ativo IS TRUE ORDER BY codigo';
        $rs = $this->getEntityManager()->getConnection()->fetchAll($sql);
        foreach ($rs as $r) {
            $results[$r['codigo']] = $r;
        }
        return $results;
    }
}
