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
 *     normalizationContext={"groups"={"notaFiscalEvento","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"notaFiscalEvento"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/notaFiscalEvento/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "put"={"path"="/fis/notaFiscalEvento/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "delete"={"path"="/fis/notaFiscalEvento/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/notaFiscalEvento", "security"="is_granted('ROLE_FISCAL')"},
 *          "post"={"path"="/fis/notaFiscalEvento", "security"="is_granted('ROLE_FISCAL')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalEventoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalEventoRepository")
 * @ORM\Table(name="fis_nf_evento")
 *
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalEvento implements EntityId
{

    use EntityIdTrait;


    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal", inversedBy="eventos")
     * @ORM\JoinColumn(name="nota_fiscal_id", nullable=true)
     *
     * @var $notaFiscal null|NotaFiscal
     */
    public ?NotaFiscal $notaFiscal = null;

    /**
     *
     * @ORM\Column(name="tp_evento", type="integer", nullable=false)
     * @var null|int
     *
     * @Groups("notaFiscalEvento")
     */
    public ?int $tpEvento = null;

    /**
     *
     * @ORM\Column(name="nseq_evento", type="integer", nullable=true)
     * @var null|int
     * @Groups("notaFiscalEvento")
     */
    public ?int $nSeqEvento = null;

    /**
     *
     * @ORM\Column(name="desc_evento", type="string", length=200, nullable=false)
     * @var null|string
     *
     * @Groups("notaFiscalEvento")
     */
    public ?string $descEvento = null;

    /**
     *
     * @ORM\Column(name="xml", type="string", nullable=true)
     * @var null|string
     *
     * @NotUppercase()
     */
    public ?string $xml = null;

    
    /**
     * @return string|null
     */
    public function getXml(): ?string
    {
        return $this->xml;
    }

    /**
     * @param string|null $xml
     * @return NotaFiscalEvento
     */
    public function setXml(?string $xml): NotaFiscalEvento
    {
        $this->xml = $xml;
        return $this;
    }

    /**
     * @return SimpleXMLElement|null
     */
    public function getXMLDecoded(): ?SimpleXMLElement
    {
        if ($this->getXml()) {
            $xmlUnzip = gzdecode(base64_decode($this->getXml()));
            return simplexml_load_string($xmlUnzip);
        }
        return null;
    }


}