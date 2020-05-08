<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoSaldoRepository")
 * @ORM\Table(name="est_produto_saldo")
 *
 * @author Carlos Eduardo Pauluk
 */
class ProdutoSaldo implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="qtde", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $qtde;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto", inversedBy="saldos")
     * @ORM\JoinColumn(name="produto_id", nullable=false)
     *
     * @var null|Produto
     */
    public ?Produto $produto;

}