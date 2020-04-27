<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 * Repository para a entidade Carteira.
 *
 * @author Carlos Eduardo Pauluk
 */
class CarteiraRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Carteira::class;
    }


}
