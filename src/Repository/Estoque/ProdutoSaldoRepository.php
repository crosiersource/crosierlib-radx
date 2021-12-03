<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoSaldo;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class ProdutoSaldoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return ProdutoSaldo::class;
    }
}
