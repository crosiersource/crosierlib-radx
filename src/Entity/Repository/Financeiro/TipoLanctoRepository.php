<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\TipoLancto;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;

/**
 * Repository para a entidade TipoLancto.
 *
 * @author Carlos Eduardo Pauluk
 */
class TipoLanctoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return TipoLancto::class;
    }

}
