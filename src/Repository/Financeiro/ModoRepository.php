<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;

/**
 * Repository para a entidade Modo.
 *
 * @author Carlos Eduardo Pauluk
 */
class ModoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Modo::class;
    }

    /**
     * @param array $sel
     * @return false|string|void
     */
    public function getSelect2js($sel = [])
    {
        $rs = $this->getEntityManager()->getConnection()->fetchAllAssociative('SELECT * FROM fin_modo ORDER BY codigo');
        if (!is_array($sel)) {
            $sel = [$sel];
        }
        $r = [];
        foreach ($rs as $e) {
            $r[] = [
                'id' => $e['id'],
                'text' => $e['codigo'] . ' - ' . $e['descricao'],
                'selected' => in_array($e['id'], $sel) ? 'selected' : ''
            ];
        }
        return json_encode($r);
    }
}
