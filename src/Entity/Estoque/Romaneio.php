<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"romaneio","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"romaneio"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/romaneio/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/romaneio/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/romaneio/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/romaneio", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/romaneio", "security"="is_granted('ROLE_ESTOQUE')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\RomaneioEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\RomaneioRepository")
 * @ORM\Table(name="est_romaneio")
 *
 * @author Carlos Eduardo Pauluk
 */
class Romaneio implements EntityId
{

    use EntityIdTrait;


    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Fornecedor")
     * @ORM\JoinColumn(name="fornecedor_id")
     * @Groups("romaneio")
     *
     * @var null|Fornecedor
     */
    public ?Fornecedor $fornecedor = null;


    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal")
     * @ORM\JoinColumn(name="notafiscal_id", nullable=true)
     *
     * @var $notaFiscal null|NotaFiscal
     */
    public ?NotaFiscal $notaFiscal = null;


    /**
     * 'INICIADO'
     * 'ENTREGUE PARCIAL'
     * 'FINALIZADO'
     * 'CANCELADO'
     *
     * @ORM\Column(name="status", type="string")
     * @Groups("romaneio")
     *
     * @var null|string
     */
    public ?string $status = 'INICIADO';


    /**
     *
     * @ORM\Column(name="dt_emissao", type="datetime")
     * @Groups("romaneio")
     *
     * @var null|DateTime
     */
    public ?DateTime $dtEmissao = null;


    /**
     *
     * @ORM\Column(name="dt_prev_entrega", type="datetime")
     * @Groups("romaneio")
     *
     * @var null|DateTime
     */
    public ?DateTime $dtPrevEntrega = null;


    /**
     *
     * @ORM\Column(name="dt_entrega", type="datetime")
     * @Groups("romaneio")
     *
     * @var null|DateTime
     */
    public ?DateTime $dtEntrega = null;


    /**
     *
     * @ORM\Column(name="prazos_pagto", type="string")
     * @Groups("romaneio")
     *
     * @var null|string
     */
    public ?string $prazosPagto = null;


    /**
     *
     * @ORM\Column(name="valor_total", type="decimal", precision=15, scale=2)
     * @Groups("romaneio")
     *
     * @var null|float
     */
    public ?float $valorTotal = null;


    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("romaneio")
     */
    public ?array $jsonData = null;


    /**
     *
     * @var null|RomaneioItem[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="RomaneioItem",
     *      cascade={"persist"},
     *      mappedBy="romaneio",
     *      orphanRemoval=true)
     * @ORM\OrderBy({"ordem" = "ASC"})
     * @Groups("romaneio")
     */
    public $itens;


    public function __construct()
    {
        $this->itens = new ArrayCollection();
    }

    /**
     * @param RomaneioItem|null $item
     */
    public function addItem(?RomaneioItem $item): void
    {
        if (!$this->itens->contains($item)) {
            $item->romaneio = $this;
            $this->itens->add($item);
        }
    }
}
    