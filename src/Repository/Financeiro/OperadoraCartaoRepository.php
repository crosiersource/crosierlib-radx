<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\OperadoraCartao;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository para a entidade OperadoraCartao.
 *
 * @author Carlos Eduardo Pauluk
 */
class OperadoraCartaoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return OperadoraCartao::class;
    }

    public function handleFrombyFilters(QueryBuilder $qb)
    {
        return $qb->from($this->getEntityClass(), 'e')
            ->join(Carteira::class, 'c', 'WITH', 'e.carteira = c');
    }

    /**
     * @param array $sel
     * @return false|string|void
     */
    public function getSelect2js($sel = [])
    {
        $rs = $this->getEntityManager()->getConnection()->fetchAllAssociative('SELECT * FROM fin_operadora_cartao ORDER BY descricao');
        if (!is_array($sel)) {
            $sel = [$sel];
        }
        $r = [];
        foreach ($rs as $e) {
            $r[] = [
                'id' => $e['id'],
                'text' => $e['descricao'],
                'selected' => in_array($e['id'], $sel) ? 'selected' : ''
            ];
        }
        return json_encode($r);
    }
}
    