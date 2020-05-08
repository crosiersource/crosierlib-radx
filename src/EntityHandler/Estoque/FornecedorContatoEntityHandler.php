<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\Entity\Base\FornecedorContato;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class FornecedorContatoEntityHandler extends EntityHandler
{

    public function getEntityClass(): string
    {
        return FornecedorContato::class;
    }
}