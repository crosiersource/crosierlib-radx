<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoSaldo;

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