<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalItemRepository")
 * @ORM\Table(name="fis_nf_item")
 */
class NotaFiscalItem implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal", inversedBy="itens")
     * @ORM\JoinColumn(name="nota_fiscal_id")
     *
     * @var $notaFiscal null|NotaFiscal
     */
    private ?NotaFiscal $notaFiscal;


    /**
     *
     * @ORM\Column(name="codigo", type="string")
     * @var null|string
     */
    private ?string $codigo;

    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @var null|string
     */
    private ?string $descricao;

    /**
     *
     * @ORM\Column(name="cfop", type="string")
     * @var null|string
     */
    private ?string $cfop;

    /**
     *
     * @ORM\Column(name="ean", type="string")
     * @var null|string
     */
    private ?string $ean;

    /**
     *
     * @ORM\Column(name="csosn", type="integer")
     * @var null|int
     */
    private ?int $csosn;

    /**
     *
     * @ORM\Column(name="ncm", type="string", length=20)
     * @var null|string
     */
    private ?string $ncm;

    /**
     *
     * @ORM\Column(name="cest", type="string",  length=20)
     * @var null|string
     */
    private ?string $cest;

    /**
     *
     * @ORM\Column(name="cst", type="string")
     * @var null|string
     */
    private ?string $cst;

    /**
     *
     * @ORM\Column(name="ordem", type="integer")
     * @var null|int
     */
    private ?int $ordem;

    /**
     *
     * @ORM\Column(name="qtde", type="decimal", precision=15, scale=2)
     * @var null|float
     */
    private ?float $qtde;

    /**
     *
     * @ORM\Column(name="unidade", type="string", length=50)
     * @var null|string
     */
    private ?string $unidade;

    /**
     *
     * @ORM\Column(name="valor_total", type="decimal", precision=15, scale=2)
     * @var null|float
     */
    private ?float $valorTotal;

    /**
     *
     * @ORM\Column(name="valor_unit", type="decimal", precision=15, scale=2)
     * @var null|float
     */
    private ?float $valorUnit;

    /**
     *
     * @ORM\Column(name="valor_desconto", type="decimal",  precision=15, scale=2)
     * @var null|float
     */
    private ?float $valorDesconto;

    /**
     *
     * @ORM\Column(name="sub_total", type="decimal", precision=15, scale=2)
     * @var null|float
     */
    private ?float $subTotal;

    /**
     *
     * @ORM\Column(name="icms_valor", type="decimal",  precision=15, scale=2)
     * @var null|float
     */
    private ?float $icmsValor;

    /**
     *
     * @ORM\Column(name="icms_mod_bc", type="string")
     * @var null|string
     */
    private ?string $icmsModBC;

    /**
     *
     * @ORM\Column(name="icms_valor_bc", type="decimal",  precision=15, scale=2)
     * @var null|float
     */
    private ?float $icmsValorBc;

    /**
     *
     * @ORM\Column(name="icms", type="decimal",  precision=15, scale=2)
     * @var null|float
     */
    private ?float $icmsAliquota;


    /**
     *
     * @ORM\Column(name="pis_valor", type="decimal",  precision=15, scale=2)
     * @var null|float
     */
    private ?float $pisValor;

    /**
     *
     * @ORM\Column(name="pis_valor_bc", type="decimal",  precision=15, scale=2)
     * @var null|float
     */
    private ?float $pisValorBc;

    /**
     *
     * @ORM\Column(name="pis", type="decimal",  precision=15, scale=2)
     * @var null|float
     */
    private ?float $pisAliquota;


    /**
     *
     * @ORM\Column(name="cofins_valor", type="decimal",  precision=15, scale=2)
     * @var null|float
     */
    private ?float $cofinsValor;

    /**
     *
     * @ORM\Column(name="cofins_valor_bc", type="decimal",  precision=15, scale=2)
     * @var null|float
     */
    private ?float $cofinsValorBc;

    /**
     *
     * @ORM\Column(name="cofins", type="decimal",  precision=15, scale=2)
     * @var null|float
     */
    private ?float $cofinsAliquota;

    /**
     *
     * @ORM\Column(name="ncm_existente", type="boolean")
     * @var null|bool
     */
    private ?bool $ncmExistente;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     */
    public ?array $jsonData = null;

    /**
     * @return null|string
     */
    public function getCfop(): ?string
    {
        return $this->cfop;
    }

    /**
     * @param null|string $cfop
     * @return NotaFiscalItem
     */
    public function setCfop(?string $cfop): NotaFiscalItem
    {
        $this->cfop = $cfop;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    /**
     * @param null|string $codigo
     * @return NotaFiscalItem
     */
    public function setCodigo(?string $codigo): NotaFiscalItem
    {
        $this->codigo = $codigo;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEan(): ?string
    {
        return $this->ean;
    }

    /**
     * @param string|null $ean
     * @return NotaFiscalItem
     */
    public function setEan(?string $ean): NotaFiscalItem
    {
        $this->ean = $ean;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    /**
     * @param null|string $descricao
     * @return NotaFiscalItem
     */
    public function setDescricao(?string $descricao): NotaFiscalItem
    {
        $this->descricao = $descricao;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCsosn(): ?int
    {
        return $this->csosn;
    }

    /**
     * @param int|null $csosn
     * @return NotaFiscalItem
     */
    public function setCsosn(?int $csosn): NotaFiscalItem
    {
        $this->csosn = $csosn;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getNcm(): ?string
    {
        return $this->ncm;
    }

    /**
     * @param null|string $ncm
     * @return NotaFiscalItem
     */
    public function setNcm(?string $ncm): NotaFiscalItem
    {
        $this->ncm = $ncm;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCest(): ?string
    {
        return $this->cest;
    }

    /**
     * @param string|null $cest
     * @return NotaFiscalItem
     */
    public function setCest(?string $cest): NotaFiscalItem
    {
        $this->cest = $cest;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCst(): ?string
    {
        return $this->cst;
    }

    /**
     * @param string|null $cst
     * @return NotaFiscalItem
     */
    public function setCst(?string $cst): NotaFiscalItem
    {
        $this->cst = $cst;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getOrdem(): ?int
    {
        return $this->ordem;
    }

    /**
     * @param int|null $ordem
     * @return NotaFiscalItem
     */
    public function setOrdem(?int $ordem): NotaFiscalItem
    {
        $this->ordem = $ordem;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getQtde(): ?float
    {
        return $this->qtde;
    }

    /**
     * @param float|null $qtde
     * @return NotaFiscalItem
     */
    public function setQtde(?float $qtde): NotaFiscalItem
    {
        $this->qtde = $qtde;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getUnidade(): ?string
    {
        return $this->unidade;
    }

    /**
     * @param null|string $unidade
     * @return NotaFiscalItem
     */
    public function setUnidade(?string $unidade): NotaFiscalItem
    {
        $this->unidade = $unidade;
        return $this;
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
     * @return NotaFiscalItem
     */
    public function setValorTotal(?float $valorTotal): NotaFiscalItem
    {
        $this->valorTotal = $valorTotal;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getValorUnit(): ?float
    {
        return $this->valorUnit;
    }

    /**
     * @param float|null $valorUnit
     * @return NotaFiscalItem
     */
    public function setValorUnit(?float $valorUnit): NotaFiscalItem
    {
        $this->valorUnit = $valorUnit;
        return $this;
    }

    /**
     * @return NotaFiscal|null
     */
    public function getNotaFiscal(): ?NotaFiscal
    {
        return $this->notaFiscal;
    }

    /**
     * @param NotaFiscal|null $notaFiscal
     * @return NotaFiscalItem
     */
    public function setNotaFiscal(?NotaFiscal $notaFiscal): NotaFiscalItem
    {
        $this->notaFiscal = $notaFiscal;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getValorDesconto(): ?float
    {
        return $this->valorDesconto;
    }

    /**
     * @param float|null $valorDesconto
     * @return NotaFiscalItem
     */
    public function setValorDesconto(?float $valorDesconto): NotaFiscalItem
    {
        $this->valorDesconto = $valorDesconto;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getSubTotal(): ?float
    {
        return $this->subTotal;
    }

    /**
     * @param float|null $subTotal
     * @return NotaFiscalItem
     */
    public function setSubTotal(?float $subTotal): NotaFiscalItem
    {
        $this->subTotal = $subTotal;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getIcmsValor(): ?float
    {
        return $this->icmsValor;
    }

    /**
     * @param float|null $icmsValor
     * @return NotaFiscalItem
     */
    public function setIcmsValor(?float $icmsValor): NotaFiscalItem
    {
        $this->icmsValor = $icmsValor;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getIcmsModBC(): ?string
    {
        return $this->icmsModBC;
    }

    /**
     * @param string|null $icmsModBC
     * @return NotaFiscalItem
     */
    public function setIcmsModBC(?string $icmsModBC): NotaFiscalItem
    {
        $this->icmsModBC = $icmsModBC;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getIcmsValorBc(): ?float
    {
        return $this->icmsValorBc;
    }

    /**
     * @param float|null $icmsValorBc
     * @return NotaFiscalItem
     */
    public function setIcmsValorBc(?float $icmsValorBc): NotaFiscalItem
    {
        $this->icmsValorBc = $icmsValorBc;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getIcmsAliquota(): ?float
    {
        return $this->icmsAliquota;
    }

    /**
     * @param float|null $icmsAliquota
     * @return NotaFiscalItem
     */
    public function setIcmsAliquota(?float $icmsAliquota): NotaFiscalItem
    {
        $this->icmsAliquota = $icmsAliquota;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPisValor(): ?float
    {
        return $this->pisValor;
    }

    /**
     * @param float|null $pisValor
     * @return NotaFiscalItem
     */
    public function setPisValor(?float $pisValor): NotaFiscalItem
    {
        $this->pisValor = $pisValor;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPisValorBc(): ?float
    {
        return $this->pisValorBc;
    }

    /**
     * @param float|null $pisValorBc
     * @return NotaFiscalItem
     */
    public function setPisValorBc(?float $pisValorBc): NotaFiscalItem
    {
        $this->pisValorBc = $pisValorBc;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPisAliquota(): ?float
    {
        return $this->pisAliquota;
    }

    /**
     * @param float|null $pisAliquota
     * @return NotaFiscalItem
     */
    public function setPisAliquota(?float $pisAliquota): NotaFiscalItem
    {
        $this->pisAliquota = $pisAliquota;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCofinsValor(): ?float
    {
        return $this->cofinsValor;
    }

    /**
     * @param float|null $cofinsValor
     * @return NotaFiscalItem
     */
    public function setCofinsValor(?float $cofinsValor): NotaFiscalItem
    {
        $this->cofinsValor = $cofinsValor;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCofinsValorBc(): ?float
    {
        return $this->cofinsValorBc;
    }

    /**
     * @param float|null $cofinsValorBc
     * @return NotaFiscalItem
     */
    public function setCofinsValorBc(?float $cofinsValorBc): NotaFiscalItem
    {
        $this->cofinsValorBc = $cofinsValorBc;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCofinsAliquota(): ?float
    {
        return $this->cofinsAliquota;
    }

    /**
     * @param float|null $cofinsAliquota
     * @return NotaFiscalItem
     */
    public function setCofinsAliquota(?float $cofinsAliquota): NotaFiscalItem
    {
        $this->cofinsAliquota = $cofinsAliquota;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getNcmExistente(): ?bool
    {
        return $this->ncmExistente;
    }

    /**
     * @param bool|null $ncmExistente
     * @return NotaFiscalItem
     */
    public function setNcmExistente(?bool $ncmExistente): NotaFiscalItem
    {
        $this->ncmExistente = $ncmExistente;
        return $this;
    }

    public function calculaTotais(): void
    {
        if ($this->getQtde() === null || $this->getValorUnit() === null) {
            return;
        }

        $this->valorDesconto = $this->valorDesconto ?? 0.0;
        $this->subTotal = $this->getQtde() * $this->getValorUnit();
        $this->valorTotal = $this->subTotal; // - $this->valorDesconto;
    }
}