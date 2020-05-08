<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoSaldo;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class ProdutoSaldoEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return ProdutoSaldo::class;
    }
}