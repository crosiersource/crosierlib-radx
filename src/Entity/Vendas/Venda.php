<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Vendas;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;
use CrosierSource\CrosierLibRadxBundle\Entity\RH\Colaborador;
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
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\RH\Colaborador")
     * @ORM\JoinColumn(name="vendedor_id")
     * @Groups("entity")
     *
     * @var null|Colaborador
     */
    public ?Colaborador $vendedor = null;

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
    private ?float $valorTotal = null;

    /**
     *
     * @ORM\Column(name="status", type="string")
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $status = null;

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
     *      targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaItem",
     *      cascade={"refresh"},
     *      mappedBy="venda",
     *      orphanRemoval=true)
     * @ORM\OrderBy({"ordem" = "ASC"})
     * @Groups("entity")
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
     * @Groups("entity")
     */
    public $pagtos;


    public function __construct()
    {
        $this->itens = new ArrayCollection();
        $this->pagtos = new ArrayCollection();
    }

    /**
     * @return float|null
     */
    public function getValorTotal(): ?float
    {
        return $this->valorTotal;
    }

    /**
     * @param float|null $valorTotal
     * @return Venda
     */
    public function setValorTotal(?float $valorTotal): Venda
    {
        $this->valorTotal = $valorTotal;
        return $this;
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


}
    