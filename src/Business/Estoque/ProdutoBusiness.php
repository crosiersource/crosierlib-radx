<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Estoque;


use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class ProdutoBusiness
 * @package App\Business\Estoque
 */
class ProdutoBusiness
{

    /** @var EntityManagerInterface */
    private EntityManagerInterface $doctrine;

    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }



}