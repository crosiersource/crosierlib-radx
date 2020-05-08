<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\FornecedorTipo;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class FornecedorTipoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return FornecedorTipo::class;
    }
}
