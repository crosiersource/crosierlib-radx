<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\UnidadeProduto;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class UnidadeProdutoEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return UnidadeProduto::class;
    }


}