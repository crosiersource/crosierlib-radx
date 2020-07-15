<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Unidade;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class UnidadeEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return Unidade::class;
    }


}