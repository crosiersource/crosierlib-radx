<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoImagem;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class ProdutoImagemRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return ProdutoImagem::class;
    }

}
