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
use SimpleXMLElement;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"distDFe","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"distDFe"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/distDFe/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "put"={"path"="/fis/distDFe/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "delete"={"path"="/fis/distDFe/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/distDFe", "security"="is_granted('ROLE_FISCAL')"},
 *          "post"={"path"="/fis/distDFe", "security"="is_granted('ROLE_FISCAL')"}
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
 * @ApiFilter(OrderFilter::class, properties={"id", "documento", "nome", "updated", "nsu"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\DistDFeEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\DistDFeRepository")
 * @ORM\Table(name="fis_distdfe")
 *
 * @author Carlos Eduardo Pauluk
 */
class DistDFe implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="documento", type="string")
     * @var null|string
     * @Groups("distDFe")
     */
    public ?string $documento = null;

    /**
     *
     * @ORM\Column(name="tipo_distdfe", type="string")
     * @var null|string
     * @Groups("distDFe")
     */
    public ?string $tipoDistDFe = null;

    /**
     * Se é referente a um DF próprio.
     *
     * @ORM\Column(name="proprio", type="boolean")
     * @var null|bool
     * @Groups("distDFe")
     */
    public ?bool $proprio = null;


    /**
     * Se é referente a um DF de cte.
     *
     * @ORM\Column(name="cte", type="boolean")
     * @var null|bool
     * @Groups("distDFe")
     */
    public ?bool $cte = null;

    /**
     *
     * @ORM\Column(name="chnfe", type="string", length=44)
     * @var null|string
     * @Groups("distDFe")
     */
    public ?string $chave = null;

    /**
     *
     * @ORM\Column(name="tp_evento", type="integer")
     * @var null|int
     * @Groups("distDFe")
     */
    public ?int $tpEvento = null;

    /**
     *
     * @ORM\Column(name="nseq_evento", type="integer")
     * @var null|int
     * @Groups("distDFe")
     */
    public ?int $nSeqEvento = null;

    /**
     *
     * @ORM\Column(name="nsu", type="bigint", nullable=false)
     * @var null|int
     * @Groups("distDFe")
     */
    public ?int $nsu = null;

    /**
     *
     * @ORM\Column(name="xml", type="string")
     * @var null|string
     *
     * @NotUppercase()
     */
    public ?string $xml = null;

    /**
     *
     * @ORM\Column(name="status", length=255, type="string")
     * @var null|string
     * @Groups("distDFe")
     * @NotUppercase()
     */
    public ?string $status = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal")
     * @ORM\JoinColumn(name="nota_fiscal_id")
     *
     * @var $notaFiscal null|NotaFiscal
     */
    public ?NotaFiscal $notaFiscal = null;


    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\Cte")
     * @ORM\JoinColumn(name="cte_id")
     *
     * @var $fisCte null|Cte
     */
    public ?Cte $fisCte = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalEvento")
     * @ORM\JoinColumn(name="nota_fiscal_evento_id")
     *
     * @var $notaFiscalEvento null|NotaFiscalEvento
     */
    public ?NotaFiscalEvento $notaFiscalEvento = null;


    /**     * 
     * @return SimpleXMLElement|null
     */
    public function getXMLDecoded(): ?SimpleXMLElement
    {
        if ($this->xml && $this->xml !== 'Nenhum documento localizado') {
            $xmlUnzip = gzdecode(base64_decode($this->xml));
            return simplexml_load_string($xmlUnzip);
        }
        return null;
    }

    /**
     * @Groups("distDFe")
     * @return string|null
     */
    public function getXMLDecodedAsString(): ?string
    {
        if ($this->xml && $this->xml !== 'Nenhum documento localizado') {
            return gzdecode(base64_decode($this->xml));
        }
        return null;
    }

    /**
     * Transient.
     * Para não precisar retornar toda a notaFiscal como JSON para o list.
     *
     * @Groups("distDFe")
     */
    public function getNotaFiscalId(): ?int
    {
        return isset($this->notaFiscal) && $this->notaFiscal->getId() ? $this->notaFiscal->getId() : null;
    }

    /**
     * Transient.
     * Para não precisar retornar toda o notaFiscalEvento como JSON para o list.
     *
     * @Groups("distDFe")
     */
    public function getEventoId(): ?int
    {
        return isset($this->notaFiscalEvento) && $this->notaFiscalEvento->getId() ? $this->notaFiscalEvento->getId() : null;
    }


}