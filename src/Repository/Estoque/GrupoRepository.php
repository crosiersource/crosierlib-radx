<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Grupo;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

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
