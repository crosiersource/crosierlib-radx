<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\PedidoCompra;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

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
