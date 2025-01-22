<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Vendas;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;
use CrosierSource\CrosierLibRadxBundle\Entity\RH\Colaborador;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"venda","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"venda"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/ven/venda/{id}", "security"="is_granted('ROLE_VENDAS')"},
 *          "put"={"path"="/ven/venda/{id}", "security"="is_granted('ROLE_VENDAS')"},
 *          "delete"={"path"="/ven/venda/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/ven/venda", "security"="is_granted('ROLE_VENDAS')"},
 *          "post"={"path"="/ven/venda", "security"="is_granted('ROLE_VENDAS')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "documento": "exact", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "documento", "nome", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Vendas\VendaRepository")
 * @ORM\Table(name="ven_venda")
 *
 * @author Carlos Eduardo Pauluk
 */
class Venda implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="dt_venda", type="datetime", nullable=false)
     * @Groups("venda")
     * @var null|DateTime
     */
    public ?DateTime $dtVenda = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente")
     * @ORM\JoinColumn(name="cliente_id")
     * @Groups("venda")
     * @var null|Cliente
     */
    public ?Cliente $cliente = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\RH\Colaborador")
     * @ORM\JoinColumn(name="vendedor_id")
     * @Groups("venda")
     * @var null|Colaborador
     */
    public ?Colaborador $vendedor = null;
    
    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Vendas\PlanoPagto")
     * @ORM\JoinColumn(name="plano_pagto_id")
     * @Groups("venda")
     * @var null|PlanoPagto
     */
    public ?PlanoPagto $planoPagto = null;

    /**
     * @ORM\Column(name="subtotal", type="decimal")
     * @Groups("venda")
     * @var null|float
     */
    public ?float $subtotal = null;

    /**
     * @ORM\Column(name="desconto", type="decimal")
     * @Groups("venda")
     * @var null|float
     */
    public ?float $desconto = null;
    
    /**
     * @ORM\Column(name="desconto_especial", type="decimal")
     * @Groups("venda")
     * @var null|float
     */
    public ?float $descontoEspecial = null;

    /**
     * @ORM\Column(name="historico_desconto", type="string")
     * @Groups("venda")
     * @var null|string
     */
    public ?string $historicoDesconto = null;

    /**
     * @ORM\Column(name="valor_total", type="decimal")
     * @Groups("venda")
     * @var null|float
     */
    public ?float $valorTotal = null;

    /**
     * @ORM\Column(name="status", type="string")
     * @Groups("venda")
     * @var null|string
     */
    public ?string $status = null;
    
    /**
     * @ORM\Column(name="deletado", type="boolean")
     * @Groups("venda")
     * @var null|bool
     */
    public ?bool $deletado = false;

    /**
     * @ORM\Column(name="obs", type="string")
     * @Groups("venda")
     * @var null|string
     */
    public ?string $obs = null;

    /**
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("venda")
     */
    public ?array $jsonData = null;

    /**
     *
     * @var null|VendaItem[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaItem",
     *      cascade={"all"},
     *      mappedBy="venda",
     *      orphanRemoval=true)
     * @ORM\OrderBy({"ordem" = "ASC"})
     * @Groups("venda")
     */
    public $itens;

    /**
     *
     * @var null|VendaPagto[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaPagto",
     *      cascade={"refresh"},
     *      mappedBy="venda",
     *      orphanRemoval=true)
     * @Groups("venda")
     */
    public $pagtos;


    public function __construct()
    {
        $this->itens = new ArrayCollection();
        $this->pagtos = new ArrayCollection();
    }


    /**
     * @param VendaItem|null $i
     */
    public function addItem(?VendaItem $i): void
    {
        $i->venda = $this;
        if (!$this->itens->contains($i)) {
            $this->itens->add($i);
        }
    }

    /**
     * @param VendaPagto|null $pagto
     */
    public function addPagto(?VendaPagto $pagto): void
    {
        $pagto->venda = $this;
        if (!$this->pagtos->contains($pagto)) {
            $this->pagtos->add($pagto);
        }
    }

    /**
     *
     */
    public function recalcularTotais()
    {
        $subtotal = 0.0;
        $descontos = 0.0;
        $valorTotal = 0.0;
        foreach ($this->itens as $item) {
            $subtotal = bcadd($subtotal, $item->subtotal, 2);
            $descontos = bcadd($descontos, $item->desconto, 2);
            $valorTotal = bcadd($valorTotal, $item->total, 2);
        }
        $this->subtotal = $subtotal;
        $this->desconto = $descontos;
        $this->valorTotal = $valorTotal;
    }

    public function getTotalPagtos(): float
    {
        $totalPagtos = 0.0;
        /** @var VendaPagto $pagto */
        foreach ($this->pagtos as $pagto) {
            $totalPagtos = bcadd($totalPagtos, $pagto->valorPagto, 2);
        }
        return (float)$totalPagtos;
    }


}
