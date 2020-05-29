<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Vendas;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\RH\Colaborador;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\Repository\RH\ColaboradorRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Repository para a entidade Venda.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class VendaRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Venda::class;
    }

    /**
     * @param \DateTime $dtVenda
     * @param $pv
     * @return null|Venda
     * @throws \Exception
     */
    public function findByDtVendaAndPV(\DateTime $dtVenda, $pv): ?Venda
    {
        $r = $this->getEntityManager()->getConnection()
            ->fetchAll('SELECT id FROM ven_venda WHERE json_data->>"$.pv" = :pv AND date(dt_venda) = :dtVenda',
                [
                    'pv' => $pv,
                    'dtVenda' => $dtVenda->format('Y-m-d')
                ]
            );

        if (!$r || count($r) === 0) {
            return null;
        }

        if (count($r) > 1) {
            throw new \Exception('Mais de uma venda encontrada para [' . $dtVenda->format('d/m/Y') . '] e [' . $pv . ']');
        }

        /** @var Venda $venda */
        $venda = $this->find($r[0]['id']);
        return $venda;
    }

    /**
     * @param $pv
     * @param $mesano
     * @return Venda|null |null
     * @throws \Exception
     */
    public function findByPVAndMesAno($pv, $mesano)
    {
        $r = $this->getEntityManager()->getConnection()
            ->fetchAll('SELECT id FROM ven_venda WHERE json_data->>"$.pv" = :pv AND DATE_FORMAT(dt_venda, \'%Y%m\') = :mesano',
                [
                    'pv' => $pv,
                    'mesno' => $mesano
                ]
            );

        if (!$r || count($r) === 0) {
            return null;
        }

        if (count($r) > 1) {
            throw new \Exception('Mais de uma venda encontrada para [' . $mesano . '] e [' . $pv . ']');
        }

        /** @var Venda $venda */
        $venda = $this->find($r[0]['id']);
        return $venda;
    }

    /**
     * @param $pv
     * @return Venda|null
     * @throws \Exception
     */
    public function findByPV($pv)
    {
        $hoje = new \DateTime();
        $mesano = $hoje->format('Ym');
        return $this->findByPVAndMesAno($pv, $mesano);
    }


    /**
     *
     *
     * @param \DateTime $dtIni
     * @param \DateTime $dtFim
     * @param $codVendedorIni
     * @param $codVendedorFim
     * @return mixed
     */
    public function findTotalVendasPorPeriodoVendedores(\DateTime $dtIni, \DateTime $dtFim, $codVendedorIni = null, $codVendedorFim = null)
    {

        $sql = 'SELECT vendedor.id as vendedor_id, sum(valor_total) as total ' .
            'FROM ven_venda v, rh_colaborador vendedor, ven_plano_pagto pp ' .
            'WHERE v.vendedor_id = vendedor.id AND ' .
            'v.plano_pagto_id = pp.id AND ' .
            "pp.codigo != '6.00' AND " .
            'v.deletado != true AND ' .
            'v.dt_venda BETWEEN :dtIni and :dtFim AND ' .
            'vendedor.codigo BETWEEN :codVendedorIni AND :codVendedorFim ' .
            'GROUP BY v.vendedor_id ORDER BY total DESC';

        $rsm = new ResultSetMapping();
        $qry = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $dtIni->setTime(0, 0, 0, 0);
        $qry->setParameter('dtIni', $dtIni);
        $dtFim->setTime(23, 59, 59, 999999);
        $qry->setParameter('dtFim', $dtFim);

        if ($codVendedorIni !== null and $codVendedorFim !== null) {
            $qry->setParameter('codVendedorIni', $codVendedorIni);
            $qry->setParameter('codVendedorFim', $codVendedorFim);
        }

//        $qry->getSQL();
//        $qry->getParameters();
        $rsm->addScalarResult('vendedor_id', 'vendedor_id');
        $rsm->addScalarResult('total', 'total');
        $results = $qry->getResult();

        $rc = [];

        $total = 0.0;

        $rc['rs'] = [];


        /** @var ColaboradorRepository $repoColaborador */
        $repoColaborador = $this->getEntityManager()->getRepository(Colaborador::class);


        foreach ($results as $r) {
            $vendedor = $repoColaborador->find($r['vendedor_id']);
            $rc['rs'][] = ['vendedor' => $vendedor, 'total' => $r['total']];
            $total = bcadd($total, $r['total'], 2);
        }

        $rc['total'] = $total;

        return $rc;
    }

    /**
     * @param \DateTime $dtIni
     * @param \DateTime $dtFim
     * @param bool $addTodos
     * @return array
     */
    public function findVendedoresComVendasNoPeriodo_select2js(\DateTime $dtIni, \DateTime $dtFim, bool $addTodos = true)
    {
        $sql = 'select json_data->>"$.vendedor_codigo" as codigo, json_data->>"$.vendedor_nome" as nome from ven_venda where dt_venda between :dtIni and :dtFim group by json_data->>"$.vendedor_codigo", json_data->>"$.vendedor_nome" order by json_data->>"$.vendedor_nome"';
        $dtIniF = $dtIni->setTime(0, 0)->format('Y-m-d H:i:s');
        $dtFimF = $dtFim->setTime(23, 59, 59, 9999)->format('Y-m-d H:i:s');
        /** @var Connection $conn */
        $conn = $this->getEntityManager()->getConnection();
        $rs = $conn->fetchAll($sql, ['dtIni' => $dtIniF, 'dtFim' => $dtFimF]);
        $result = [];
        if ($addTodos) {
            $result[] = [
                'id' => '',
                'text' => 'TODOS'
            ];
        }
        foreach ($rs as $r) {
            if ($r['nome']) {
                $result[] = [
                    'id' => $r['codigo'],
                    'text' => $r['nome']
                ];
            }
        }
        return $result;
    }

}
