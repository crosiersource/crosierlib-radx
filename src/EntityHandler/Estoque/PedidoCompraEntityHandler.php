<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\PedidoCompra;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class PedidoCompraEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return PedidoCompra::class;
    }
}