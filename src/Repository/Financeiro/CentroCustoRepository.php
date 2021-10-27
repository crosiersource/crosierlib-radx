<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CentroCusto;

/**
 * Repository para a entidade CentroCusto.
 *
 * @author Carlos Eduardo Pauluk
 */
class CentroCustoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return CentroCusto::class;
    }

    /**
     * @param array $sel
     * @return false|string|void
     * @throws \Exception
     */
    public function getSelect2js($sel = [])
    {
        $rs = $this->getEntityManager()->getConnection()->fetchAllAssociative('SELECT id, codigo, descricao FROM fin_centrocusto ORDER BY codigo');
        if (!is_array($sel)) {
            $sel = [$sel];
        }
        foreach ($rs as $e) {
            $r[] = [
                'id' => $e['id'],
                'text' => str_pad($e['codigo'], 2, '0', STR_PAD_LEFT) . ' - ' . $e['descricao'],
                'selected' => in_array($e['id'], $sel) ? 'selected' : ''
            ];
        }
        return json_encode($r);
    }
}
