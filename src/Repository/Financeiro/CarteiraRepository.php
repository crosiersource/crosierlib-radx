<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;

/**
 * Repository para a entidade Carteira.
 *
 * @author Carlos Eduardo Pauluk
 */
class CarteiraRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Carteira::class;
    }

    /**
     * @param array $sel
     * @return false|string|void
     * @throws \Exception
     */
    public function getSelect2js($sel = [])
    {
        $rs = $this->getEntityManager()->getConnection()->fetchAll('SELECT id, codigo, descricao FROM fin_carteira ORDER BY codigo');
        if (!is_array($sel)) {
            $sel = [$sel];
        }
        foreach ($rs as $e) {
            $r[] = [
                'id' => $e['id'],
                'text' => str_pad($e['codigo'], 3, '0', STR_PAD_LEFT) . ' - ' . $e['descricao'],
                'selected' => in_array($e['id'], $sel) ? 'selected' : ''
            ];
        }
        return json_encode($r);
    }


}
