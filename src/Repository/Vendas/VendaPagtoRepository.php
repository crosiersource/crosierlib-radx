<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Vendas;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaPagto;

/**
 * @author Carlos Eduardo Pauluk
 */
class VendaPagtoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return VendaPagto::class;
    }
}
