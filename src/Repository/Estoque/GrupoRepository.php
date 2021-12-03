<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Grupo;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class GrupoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Grupo::class;
    }

}
