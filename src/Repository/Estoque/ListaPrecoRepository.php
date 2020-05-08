<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ListaPreco;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class ListaPrecoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return ListaPreco::class;
    }

}
