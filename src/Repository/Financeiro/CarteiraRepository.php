<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;

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
