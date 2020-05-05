<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\RegistroConferencia;

/**
 * Repository para a entidade RegistroConferencia.
 *
 * @author Carlos Eduardo Pauluk
 */
class RegistroConferenciaRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return RegistroConferencia::class;
    }
}
            