<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Ecommerce;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Ecommerce\MercadoLivrePergunta;

/**
 * @author Carlos Eduardo Pauluk
 */
class MercadoLivrePerguntaRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return MercadoLivrePergunta::class;
    }
}
