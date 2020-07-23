<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\EntradaItemRepository")
 * @ORM\Table(name="est_entrada_item")
 *
 * @author Carlos Eduardo Pauluk
 */
class EntradaItem implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Entrada", inversedBy="itens")
     * @ORM\JoinColumn(name="entrada_id")
     *
     * @var null|Entrada
     */
    public ?Entrada $entrada = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto")
     * @ORM\JoinColumn(name="produto_id", nullable=false)
     *
     * @var null|Produto
     */
    public ?Produto $produto = null;

    /**
     *
     * @ORM\Column(name="qtde", type="decimal", precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $qtde = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Unidade")
     * @ORM\JoinColumn(name="unidade_id", nullable=false)
     *
     * @var null|Unidade
     */
    public ?Unidade $unidade = null;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;


}