<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"pedidoCompra","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"pedidoCompra"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/pedidoCompra/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/pedidoCompra/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/pedidoCompra/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/pedidoCompra", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/pedidoCompra", "security"="is_granted('ROLE_ESTOQUE')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "documento": "exact", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "documento", "nome", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\PedidoCompraEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\PedidoCompraRepository")
 * @ORM\Table(name="est_pedidocompra")
 *
 * @author Carlos Eduardo Pauluk
 */
class PedidoCompra implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="dt_emissao", type="datetime")
     * @Groups("pedidoCompra")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtEmissao = null;

    /**
     *
     * @ORM\Column(name="dt_prev_entrega", type="datetime")
     * @Groups("pedidoCompra")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtPrevEntrega = null;

    /**
     *
     * @ORM\Column(name="prazos_pagto", type="string")
     * @Groups("pedidoCompra")
     *
     * @var null|string
     */
    public ?string $prazosPagto = null;

    /**
     *
     * @ORM\Column(name="responsavel", type="string")
     * @Groups("pedidoCompra")
     *
     * @var null|string
     */
    public ?string $responsavel = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Fornecedor")
     * @ORM\JoinColumn(name="fornecedor_id")
     * @Groups("pedidoCompra")
     *
     * @var null|Fornecedor
     */
    public ?Fornecedor $fornecedor = null;

    /**
     *
     * @ORM\Column(name="subtotal", type="decimal", precision=15, scale=2)
     * @Groups("pedidoCompra")
     *
     * @var null|float
     */
    public ?float $subtotal = null;

    /**
     *
     * @ORM\Column(name="desconto", type="decimal", precision=15, scale=2)
     * @var null|float
     *
     * @Groups("pedidoCompra")
     */
    public ?float $desconto = null;

    /**
     *
     * @ORM\Column(name="total", type="decimal", precision=15, scale=2)
     * @Groups("pedidoCompra")
     *
     * @var null|float
     */
    public ?float $total = null;

    /**
     * 'INICIADO'
     * 'ENVIADO'
     * 'ENTREGUE PARCIAL'
     * 'FINALIZADO'
     * 'CANCELADO'
     *
     * @ORM\Column(name="status", type="string")
     * @Groups("pedidoCompra")
     *
     * @var null|string
     */
    public ?string $status = 'INICIADO';

    /**
     *
     * @ORM\Column(name="obs", type="string")
     * @Groups("pedidoCompra")
     *
     * @var null|string
     */
    public ?string $obs = null;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("pedidoCompra")
     */
    public ?array $jsonData = null;

    /**
     *
     * @var null|PedidoCompraItem[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="PedidoCompraItem",
     *      cascade={"persist"},
     *      mappedBy="pedidoCompra",
     *      orphanRemoval=true)
     * @ORM\OrderBy({"ordem" = "ASC"})
     * @Groups("pedidoCompra")
     */
    public $itens;


    public function __construct()
    {
        $this->itens = new ArrayCollection();
    }

    public function addItem(?PedidoCompraItem $item): void
    {
        if (!$this->itens->contains($item)) {
            $this->itens->add($item);
        }
    }
}
    