<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalCartaCorrecao;

/**
 * Repository para a entidade NotaFiscalCartaCorrecao.
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class NotaFiscalCartaCorrecaoRepository extends FilterRepository
{

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return NotaFiscalCartaCorrecao::class;
    }


}
