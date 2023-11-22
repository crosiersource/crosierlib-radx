<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"notaFiscalItem","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"notaFiscalItem"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/notaFiscalItem/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "put"={"path"="/fis/notaFiscalItem/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "delete"={"path"="/fis/notaFiscalItem/{id}", "security"="is_granted('ROLE_FISCAL')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/notaFiscalItem", "security"="is_granted('ROLE_FISCAL')"},
 *          "post"={"path"="/fis/notaFiscalItem", "security"="is_granted('ROLE_FISCAL')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={
 *     "id": "exact",
 *     "notaFiscal": "exact"
 * })
 *
 * @ApiFilter(OrderFilter::class, properties={
 *     "id",
 *     "updated",
 *     "ordem",
 *     "codigo",
 *     "cfop",
 *     "ncm",
 *     "valorUnit",
 *     "qtde",
 *     "valorTotal"
 * }, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalItemEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalItemRepository")
 * @ORM\Table(name="fis_nf_item")
 */
class NotaFiscalItem implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal", inversedBy="itens")
     * @ORM\JoinColumn(name="nota_fiscal_id")
     * @Groups("notaFiscalItem")
     * @var $notaFiscal null|NotaFiscal
     */
    public ?NotaFiscal $notaFiscal = null;

    /**
     * @ORM\Column(name="ordem", type="integer", nullable=true)
     * @Groups("notaFiscalItem")
     * @var null|int
     */
    public ?int $ordem = null;

    /**
     * @ORM\Column(name="codigo", type="string", nullable=false)
     * @Groups("notaFiscalItem")
     * @var null|string
     */
    public ?string $codigo = null;

    /**
     * @ORM\Column(name="descricao", type="string", nullable=false)
     * @Groups("notaFiscalItem")
     * @var null|string
     */
    public ?string $descricao = null;

    /**
     * @ORM\Column(name="cfop", type="string", nullable=false)
     * @Groups("notaFiscalItem")
     * @var null|string
     */
    public ?string $cfop = null;

    /**
     * @ORM\Column(name="ean", type="string", nullable=true)
     * @Groups("notaFiscalItem")
     * @var null|string
     */
    public ?string $ean = null;

    /**
     * @ORM\Column(name="csosn", type="integer", nullable=true)
     * @Groups("notaFiscalItem")
     * @var null|int
     */
    public ?int $csosn = null;

    /**
     * @ORM\Column(name="ncm", type="string", length=20, nullable=false)
     * @Groups("notaFiscalItem")
     * @var null|string
     */
    public ?string $ncm = null;

    /**
     * Código Especificador da Substituição Tributária.
     * 
     * @ORM\Column(name="cest", type="string",  length=20, nullable=true)
     * @Groups("notaFiscalItem")
     * @var null|string
     */
    public ?string $cest = null;

    /**
     * Código da Situação Tributária.
     * 
     * @ORM\Column(name="cst", type="string", nullable=true)
     * @Groups("notaFiscalItem")
     * @var null|string
     */
    public ?string $cst = null;

    /**
     * @ORM\Column(name="qtde", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("N")
     * @Assert\NotNull()
     * @Assert\Type(type="string")
     */
    public ?string $qtde = null;

    /**
     * @ORM\Column(name="unidade", type="string", length=50, nullable=true)
     * @Groups("notaFiscalItem")
     * @var null|string
     */
    public ?string $unidade = null;

    /**
     * @ORM\Column(name="valor_total", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $valorTotal = null;

    /**
     * @ORM\Column(name="valor_unit", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("N")
     * @Assert\NotNull()
     * @Assert\Type(type="string")
     */
    public ?string $valorUnit = null;

    /**
     * @ORM\Column(name="valor_desconto", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $valorDesconto = null;

    /**
     * @ORM\Column(name="sub_total", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $subtotal = null;

    /**
     * @ORM\Column(name="icms_valor", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $icmsValor = null;

    /**
     * @ORM\Column(name="icms_mod_bc", type="string", nullable=true)
     * @Groups("notaFiscalItem")
     * @var null|string
     */
    public ?string $icmsModBC = null;

    /**
     * @ORM\Column(name="icms_valor_bc", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $icmsValorBc = null;

    /**
     * @ORM\Column(name="icms", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $icmsAliquota = null;

    /**
     * @ORM\Column(name="pis_valor", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $pisValor = null;

    /**
     * @ORM\Column(name="pis_valor_bc", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $pisValorBc = null;

    /**
     * @ORM\Column(name="pis", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $pisAliquota = null;

    /**
     * @ORM\Column(name="cofins_valor", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $cofinsValor = null;

    /**
     * @ORM\Column(name="cofins_valor_bc", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $cofinsValorBc = null;

    /**
     * @ORM\Column(name="cofins", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $cofinsAliquota = null;

    /**
     * @ORM\Column(name="ncm_existente", type="boolean", nullable=true)
     * @Groups("notaFiscalItem")
     * @var null|bool
     */
    public ?bool $ncmExistente = null;

    /**
     * @ORM\Column(name="json_data", type="json", nullable=true)
     * @Groups("notaFiscalItem")
     * @var null|array
     * @NotUppercase()
     */
    public ?array $jsonData = null;

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("qtde")
     * @return null|float
     */
    public function getQtdeFormatted(): ?float
    {
        return (float)$this->qtde;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("qtde")
     * @param null|float $qtde
     */
    public function setQtdeFormatted(?float $qtde)
    {
        $this->qtde = $qtde;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("valorTotal")
     * @return null|float
     */
    public function getValorTotalFormatted(): ?float
    {
        return (float)$this->valorTotal;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("valorTotal")
     * @param null|float $valorTotal
     */
    public function setValorTotalFormatted(?float $valorTotal)
    {
        $this->valorTotal = $valorTotal;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("valorUnit")
     * @return null|float
     */
    public function getValorUnitFormatted(): ?float
    {
        return (float)$this->valorUnit;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("valorUnit")
     * @param null|float $valorUnit
     */
    public function setValorUnitFormatted(?float $valorUnit)
    {
        $this->valorUnit = $valorUnit;
    }
    
    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("valorDesconto")
     * @return null|float
     */
    public function getValorDescontoFormatted(): ?float
    {
        return (float)$this->valorDesconto;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("valorDesconto")
     * @param null|float $valorDesconto
     */
    public function setValorDescontoFormatted(?float $valorDesconto)
    {
        $this->valorDesconto = $valorDesconto;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("subtotal")
     * @return null|float
     */
    public function getSubTotalFormatted(): ?float
    {
        return $this->subtotal;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("subtotal")
     * @param null|float $subtotal
     */
    public function setSubTotalFormatted(?float $subtotal)
    {
        $this->subtotal = $subtotal;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("icmsValor")
     * @return null|float
     */
    public function getIcmsValorFormatted(): ?float
    {
        return (float)$this->icmsValor;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("icmsValor")
     * @param null|float $icmsValor
     */
    public function setIcmsValorFormatted(?float $icmsValor)
    {
        $this->icmsValor = $icmsValor;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("icmsValorBc")
     * @return null|float
     */
    public function getIcmsValorBcFormatted(): ?float
    {
        return (float)$this->icmsValorBc;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("icmsValorBc")
     * @param null|float $icmsValorBc
     */
    public function setIcmsValorBcFormatted(?float $icmsValorBc)
    {
        $this->icmsValorBc = $icmsValorBc;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("icmsAliquota")
     * @return null|float
     */
    public function getIcmsAliquotaFormatted(): ?float
    {
        return (float)$this->icmsAliquota;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("icmsAliquota")
     * @param null|float $icmsAliquota
     */
    public function setIcmsAliquotaFormatted(?float $icmsAliquota)
    {
        $this->icmsAliquota = $icmsAliquota;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("pisValor")
     * @return null|float
     */
    public function getPisValorFormatted(): ?float
    {
        return (float)$this->pisValor;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("pisValor")
     * @param null|float $pisValor
     */
    public function setPisValorFormatted(?float $pisValor)
    {
        $this->pisValor = $pisValor;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("pisValorBc")
     * @return null|float
     */
    public function getPisValorBcFormatted(): ?float
    {
        return (float)$this->pisValorBc;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("pisValorBc")
     * @param null|float $pisValorBc
     */
    public function setPisValorBcFormatted(?float $pisValorBc)
    {
        $this->pisValorBc = $pisValorBc;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("pisAliquota")
     * @return null|float
     */
    public function getPisAliquotaFormatted(): ?float
    {
        return (float)$this->pisAliquota;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("pisAliquota")
     * @param null|float $pisAliquota
     */
    public function setPisAliquotaFormatted(?float $pisAliquota)
    {
        $this->pisAliquota = $pisAliquota;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("cofinsValor")
     * @return null|float
     */
    public function getCofinsValorFormatted(): ?float
    {
        return (float)$this->cofinsValor;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("cofinsValor")
     * @param null|float $cofinsValor
     */
    public function setCofinsValorFormatted(?float $cofinsValor)
    {
        $this->cofinsValor = $cofinsValor;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("cofinsValorBc")
     * @return null|float
     */
    public function getCofinsValorBcFormatted(): ?float
    {
        return (float)$this->cofinsValorBc;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("cofinsValorBc")
     * @param null|float $cofinsValorBc
     */
    public function setCofinsValorBcFormatted(?float $cofinsValorBc)
    {
        $this->cofinsValorBc = $cofinsValorBc;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("cofinsAliquota")
     * @return null|float
     */
    public function getCofinsAliquotaFormatted(): ?float
    {
        return (float)$this->cofinsAliquota;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscalItem")
     * @SerializedName("cofinsAliquota")
     * @param null|float $cofinsAliquota
     */
    public function setCofinsAliquotaFormatted(?float $cofinsAliquota)
    {
        $this->cofinsAliquota = $cofinsAliquota;
    }

    public function calculaTotais(): void
    {
        if ($this->qtde === null || $this->valorUnit === null) {
            return;
        }

        $valorDesconto = (float)$this->valorDesconto ?? 0.0;
        if ((float)$this->valorDesconto !== $valorDesconto) {
            $this->valorDesconto = $valorDesconto;
        }
        $subtotal = (float)(bcmul($this->qtde, $this->valorUnit, 2));
        if ((float)$this->subtotal !== $subtotal) {
            $this->subtotal = $subtotal;
        }

        if ((float)$this->valorTotal !== (float)$this->subtotal) {
            $this->valorTotal = $this->subtotal;
        }
    }
    
}
