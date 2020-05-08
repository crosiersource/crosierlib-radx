<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\DepreciacaoPreco;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class DepreciacaoPrecoEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return DepreciacaoPreco::class;
    }


}