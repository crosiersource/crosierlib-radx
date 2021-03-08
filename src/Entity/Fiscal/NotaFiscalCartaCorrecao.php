<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"entity","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"entity"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/notaFiscalCartaCorrecao/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fis/notaFiscalCartaCorrecao/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fis/notaFiscalCartaCorrecao/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/notaFiscalCartaCorrecao", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fis/notaFiscalCartaCorrecao", "security"="is_granted('ROLE_FINAN')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalCartaCorrecaoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalCartaCorrecaoRepository")
 * @ORM\Table(name="fis_nf_cartacorrecao")
 */
class NotaFiscalCartaCorrecao implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal", inversedBy="itens")
     *
     * @var $notaFiscal null|NotaFiscal
     */
    private $notaFiscal;

    /**
     * @NotUppercase()
     * @ORM\Column(name="carta_correcao", type="string", nullable=true)
     * @var null|string
     */
    private $cartaCorrecao;

    /**
     *
     * @ORM\Column(name="seq", type="integer", nullable=true)
     * @var null|int
     */
    private $seq;


    /**
     *
     * @ORM\Column(name="dt_carta_correcao", type="datetime", nullable=false)
     * @var null|\DateTime
     */
    private $dtCartaCorrecao;

    /**
     * @NotUppercase()
     * @ORM\Column(name="msg_retorno", type="string", nullable=true)
     * @var null|string
     */
    private $msgRetorno;


    /**
     * @return null|string
     */
    public function getCartaCorrecao(): ?string
    {
        return $this->cartaCorrecao;
    }

    /**
     * @param null|string $cartaCorrecao
     * @return NotaFiscalCartaCorrecao
     */
    public function setCartaCorrecao(?string $cartaCorrecao): NotaFiscalCartaCorrecao
    {
        $this->cartaCorrecao = $cartaCorrecao;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSeq(): ?int
    {
        return $this->seq;
    }

    /**
     * @param int|null $seq
     * @return NotaFiscalCartaCorrecao
     */
    public function setSeq(?int $seq): NotaFiscalCartaCorrecao
    {
        $this->seq = $seq;
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
     * @return NotaFiscalCartaCorrecao
     */
    public function setNotaFiscal(?NotaFiscal $notaFiscal): NotaFiscalCartaCorrecao
    {
        $this->notaFiscal = $notaFiscal;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDtCartaCorrecao(): ?\DateTime
    {
        return $this->dtCartaCorrecao;
    }

    /**
     * @param \DateTime|null $dtCartaCorrecao
     * @return NotaFiscalCartaCorrecao
     */
    public function setDtCartaCorrecao(?\DateTime $dtCartaCorrecao): NotaFiscalCartaCorrecao
    {
        $this->dtCartaCorrecao = $dtCartaCorrecao;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMsgRetorno(): ?string
    {
        return $this->msgRetorno;
    }

    /**
     * @param string|null $msgRetorno
     * @return NotaFiscalCartaCorrecao
     */
    public function setMsgRetorno(?string $msgRetorno): NotaFiscalCartaCorrecao
    {
        $this->msgRetorno = $msgRetorno;
        return $this;
    }


}