<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * Entidade que guarda informações sobre o histórico da nota fiscal.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"notaFiscalHistorico","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"notaFiscalHistorico"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/notaFiscalHistorico/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fis/notaFiscalHistorico/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fis/notaFiscalHistorico/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/notaFiscalHistorico", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fis/notaFiscalHistorico", "security"="is_granted('ROLE_FINAN')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalHistoricoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalHistoricoRepository")
 * @ORM\Table(name="fis_nf_historico")
 *
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalHistorico implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal", inversedBy="historicos")
     * @ORM\JoinColumn(name="fis_nf_id", nullable=false)
     *
     * @var $fisNf null|NotaFiscal
     */
    private $notaFiscal;

    /**
     *
     * @ORM\Column(name="codigo_status", type="integer", nullable=false)
     * @var null|int
     */
    private $codigoStatus;

    /**
     *
     * @ORM\Column(name="dt_historico", type="datetime", nullable=false)
     * @var null|\DateTime
     */
    private $dtHistorico;

    /**
     *
     * @ORM\Column(name="descricao", type="string", nullable=false, length=2000)
     * @var null|string
     */
    private $descricao;

    /**
     *
     * @ORM\Column(name="obs", type="string", nullable=false, length=255)
     * @var null|string
     * @NotUppercase()
     */
    private $obs;

    /**
     * @return NotaFiscal|null
     */
    public function getNotaFiscal(): ?NotaFiscal
    {
        return $this->notaFiscal;
    }

    /**
     * @param NotaFiscal|null $notaFiscal
     * @return NotaFiscalHistorico
     */
    public function setNotaFiscal(?NotaFiscal $notaFiscal): NotaFiscalHistorico
    {
        $this->notaFiscal = $notaFiscal;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCodigoStatus(): ?int
    {
        return $this->codigoStatus;
    }

    /**
     * @param int|null $codigoStatus
     * @return NotaFiscalHistorico
     */
    public function setCodigoStatus(?int $codigoStatus): NotaFiscalHistorico
    {
        $this->codigoStatus = $codigoStatus;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDtHistorico(): ?\DateTime
    {
        return $this->dtHistorico;
    }

    /**
     * @param \DateTime|null $dtHistorico
     * @return NotaFiscalHistorico
     */
    public function setDtHistorico(?\DateTime $dtHistorico): NotaFiscalHistorico
    {
        $this->dtHistorico = $dtHistorico;
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
     * @return NotaFiscalHistorico
     */
    public function setDescricao(?string $descricao): NotaFiscalHistorico
    {
        $this->descricao = $descricao;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getObs(): ?string
    {
        return $this->obs;
    }

    /**
     * @param null|string $obs
     * @return NotaFiscalHistorico
     */
    public function setObs(?string $obs): NotaFiscalHistorico
    {
        $this->obs = $obs;
        return $this;
    }


}