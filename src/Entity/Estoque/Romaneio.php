<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
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
     * @Groups("entity")
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
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $status = 'INICIADO';


    /**
     *
     * @ORM\Column(name="dt_emissao", type="datetime")
     * @Groups("entity")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtEmissao = null;


    /**
     *
     * @ORM\Column(name="dt_prev_entrega", type="datetime")
     * @Groups("entity")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtPrevEntrega = null;


    /**
     *
     * @ORM\Column(name="dt_entrega", type="datetime")
     * @Groups("entity")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtEntrega = null;


    /**
     *
     * @ORM\Column(name="prazos_pagto", type="string")
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $prazosPagto = null;


    /**
     *
     * @ORM\Column(name="valor_total", type="decimal", precision=15, scale=2)
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $valorTotal = null;


    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
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
     * @Groups("entity")
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
    