<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Vendas;


use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\RH\Funcionario;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\Repository\RH\FuncionarioRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Psr\Log\LoggerInterface;

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

    public function findByDtVendaAndPV(\DateTime $dtVenda, $pv)
    {
        $dtVenda->setTime(0, 0, 0, 0);
        $ql = "SELECT v FROM CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda v WHERE v.dtVenda = :dtVenda AND v.pv = :pv";
        $query = $this->getEntityManager()->createQuery($ql);
        $query->setParameters(array(
            'dtVenda' => $dtVenda,
            'pv' => $pv
        ));

        $results = $query->getResult();

        if (count($results) > 1) {
            throw new \Exception('Mais de uma venda encontrada para [' . $dtVenda . '] e [' . $pv . ']');
        }

        return count($results) == 1 ? $results[0] : null;
    }

    public function findByPVAndMesAno($pv, $mesano)
    {
        $ql = "SELECT v FROM CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda v WHERE v.mesano = :mesano AND v.pv = :pv";
        $query = $this->getEntityManager()->createQuery($ql);
        $query->setParameters(array(
            'mesano' => $mesano,
            'pv' => $pv
        ));

        $results = $query->getResult();

        if (count($results) > 1) {
            throw new \Exception('Mais de uma venda encontrada para [' . $pv . '] e [' . $mesano . ']');
        }

        return count($results) == 1 ? $results[0] : null;
    }

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
     * @throws ViewException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function findTotalVendasPorPeriodoVendedores(\DateTime $dtIni, \DateTime $dtFim, $codVendedorIni = null, $codVendedorFim = null)
    {

        $sql = 'SELECT vendedor.id as vendedor_id, sum(valor_total) as total ' .
            'FROM ven_venda v, rh_funcionario vendedor, ven_plano_pagto pp ' .
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


        /** @var FuncionarioRepository $repoFuncionario */
        $repoFuncionario = $this->getEntityManager()->getRepository(Funcionario::class);


        foreach ($results as $r) {
            $vendedor = $repoFuncionario->find($r['vendedor_id']);
            $rc['rs'][] = ['vendedor' => $vendedor, 'total' => $r['total']];
            $total = bcadd($total, $r['total'], 2);
        }

        $rc['total'] = $total;

        return $rc;
    }

}
