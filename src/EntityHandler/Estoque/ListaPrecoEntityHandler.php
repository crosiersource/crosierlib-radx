<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ListaPreco;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

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