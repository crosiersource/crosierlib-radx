<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Entrada;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class EntradaRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Entrada::class;
    }

}
