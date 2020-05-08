<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoPreco;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class ProdutoPrecoEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return ProdutoPreco::class;
    }
}