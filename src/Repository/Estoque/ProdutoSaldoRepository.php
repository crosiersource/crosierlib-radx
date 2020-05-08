<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;

use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoSaldo;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

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
