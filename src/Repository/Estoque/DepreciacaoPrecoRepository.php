<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\DepreciacaoPreco;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class DepreciacaoPrecoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return DepreciacaoPreco::class;
    }

    /**
     * @param int $prazo
     * @return float|null
     */
    public function findDepreciacaoByPrazo(int $prazo): ?float
    {
        try {
            $rs = $this->getEntityManager()->getConnection()->
            fetchAssociative('SELECT porcentagem FROM est_depreciacao_preco WHERE prazo_ini <= :prazo AND prazo_fim >= :prazo', ['prazo' => $prazo]);
            return (float)($rs['porcentagem'] ?? 1);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Erro ao consultar - findDepreciacaoByPrazo');
        }
    }
}
