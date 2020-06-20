<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Romaneio;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class RomaneioRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Romaneio::class;
    }

}
