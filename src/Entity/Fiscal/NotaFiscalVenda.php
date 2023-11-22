<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"notaFiscalVenda","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"notaFiscalVenda"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/notaFiscalVenda/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "put"={"path"="/fis/notaFiscalVenda/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "delete"={"path"="/fis/notaFiscalVenda/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/notaFiscalVenda", "security"="is_granted('ROLE_FISCAL')"},
 *          "post"={"path"="/fis/notaFiscalVenda", "security"="is_granted('ROLE_FISCAL')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalVendaEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalVendaRepository")
 * @ORM\Table(name="fis_nf_venda")
 */
class NotaFiscalVenda implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal")
     * @ORM\JoinColumn(name="nota_fiscal_id", nullable=true)
     *
     * @var $notaFiscal null|NotaFiscal
     */
    public ?NotaFiscal $notaFiscal = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda")
     * @ORM\JoinColumn(name="venda_id", nullable=false)
     *
     * @var null|Venda
     */
    public ?Venda $venda = null;

    /**
     * @return NotaFiscal|null
     */
    public function getNotaFiscal(): ?NotaFiscal
    {
        return $this->notaFiscal;
    }

    /**
     * @param NotaFiscal|null $notaFiscal
     * @return NotaFiscalVenda
     */
    public function setNotaFiscal(?NotaFiscal $notaFiscal): NotaFiscalVenda
    {
        $this->notaFiscal = $notaFiscal;
        return $this;
    }

    /**
     * @return Venda|null
     */
    public function getVenda(): ?Venda
    {
        return $this->venda;
    }

    /**
     * @param Venda|null $venda
     * @return NotaFiscalVenda
     */
    public function setVenda(?Venda $venda): NotaFiscalVenda
    {
        $this->venda = $venda;
        return $this;
    }


}