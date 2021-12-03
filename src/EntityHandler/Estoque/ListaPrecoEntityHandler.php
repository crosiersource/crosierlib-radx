<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ListaPreco;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class ListaPrecoEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return ListaPreco::class;
    }
}