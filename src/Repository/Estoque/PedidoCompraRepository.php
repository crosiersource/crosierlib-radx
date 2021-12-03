<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\PedidoCompra;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class PedidoCompraRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return PedidoCompra::class;
    }

}
