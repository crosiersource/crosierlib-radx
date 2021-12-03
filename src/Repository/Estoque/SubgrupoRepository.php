<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Subgrupo;

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
