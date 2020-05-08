<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\PedidoCompra;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

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