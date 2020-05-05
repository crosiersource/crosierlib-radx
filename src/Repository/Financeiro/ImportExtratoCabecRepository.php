<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\ImportExtratoCabec;

/**
 * Repository para a entidade ImportExtratoCabec.
 *
 * @author Carlos Eduardo Pauluk
 */
class ImportExtratoCabecRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return ImportExtratoCabec::class;
    }


}
