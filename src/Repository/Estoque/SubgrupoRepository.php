<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Subgrupo;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class SubgrupoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Subgrupo::class;
    }

}
