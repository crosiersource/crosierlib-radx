<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Ecommerce;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Ecommerce\TrayVenda;

/**
 * @author Carlos Eduardo Pauluk
 */
class TrayVendaRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return TrayVenda::class;
    }
}
