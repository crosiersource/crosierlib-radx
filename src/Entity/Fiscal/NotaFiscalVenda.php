<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"entity","entityId"}},
 *     denormalizationContext={"groups"={"entity"}},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/notaFiscalVenda/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fis/notaFiscalVenda/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fis/notaFiscalVenda/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/notaFiscalVenda", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fis/notaFiscalVenda", "security"="is_granted('ROLE_FINAN')"}
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
    private $notaFiscal;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\vendas\Venda")
     * @ORM\JoinColumn(name="venda_id", nullable=false)
     *
     * @var null|Venda
     */
    private $venda;

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