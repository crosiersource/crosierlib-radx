<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\RomaneioItemRepository")
 * @ORM\Table(name="est_romaneio_item")
 *
 * @author Carlos Eduardo Pauluk
 */
class RomaneioItem implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Romaneio", inversedBy="itens")
     * @ORM\JoinColumn(name="romaneio_id")
     *
     * @var null|Romaneio
     */
    public ?Romaneio $romaneio = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto")
     * @ORM\JoinColumn(name="produto_id")
     *
     * @var null|Produto
     */
    public ?Produto $produto = null;

    /**
     *
     * @ORM\Column(name="ordem", type="integer")
     * @Groups("entity")
     *
     * @var null|integer
     */
    public ?int $ordem = null;


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
     * @ORM\Column(name="qtde_conferida", type="decimal", precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $qtdeConferida = null;


    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $descricao = null;


    /**
     *
     * @ORM\Column(name="preco_custo", type="decimal", precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $precoCusto = null;


    /**
     * @ORM\Column(name="total", type="decimal", precision=19, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $total = null;


    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;


}