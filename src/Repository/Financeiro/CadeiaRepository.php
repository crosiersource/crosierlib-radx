<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Cadeia;

/**
 * Repository para a entidade Cadeia.
 *
 * @author Carlos Eduardo Pauluk
 */
class CadeiaRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Cadeia::class;
    }


}
