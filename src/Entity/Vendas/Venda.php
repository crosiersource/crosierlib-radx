<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Vendas;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
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
     *
     * @ORM\Column(name="dt_venda", type="datetime", nullable=false)
     * @Groups("entity")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtVenda = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente")
     * @ORM\JoinColumn(name="cliente_id")
     * @Groups("entity")
     *
     * @var null|Cliente
     */
    public ?Cliente $cliente = null;

    /**
     *
     * @ORM\Column(name="subtotal", type="decimal")
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $subtotal = null;

    /**
     *
     * @ORM\Column(name="desconto", type="decimal")
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $desconto = null;

    /**
     *
     * @ORM\Column(name="valor_total", type="decimal")
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
     * @var null|VendaItem[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="VendaItem",
     *      cascade={"persist"},
     *      mappedBy="venda",
     *      orphanRemoval=true)
     * @ORM\OrderBy({"ordem" = "ASC"})
     * @Groups("entity")
     */
    public $itens;


    public function __construct()
    {
        $this->itens = new ArrayCollection();
    }


    public function addItem(?VendaItem $i): void
    {
        if (!$this->itens->contains($i)) {
            $this->itens->add($i);
        }
    }
}
    