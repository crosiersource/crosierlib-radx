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
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"notaFiscalCartaCorrecao","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"notaFiscalCartaCorrecao"},"enable_max_depth"=true},
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
 * @ApiFilter(PropertyFilter::class)
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
    public ?NotaFiscal $notaFiscal = null;

    /**
     * @NotUppercase()
     * @ORM\Column(name="carta_correcao", type="string", nullable=true)
     * @var null|string
     */
    public ?string $cartaCorrecao = null;

    /**
     *
     * @ORM\Column(name="seq", type="integer", nullable=true)
     * @var null|int
     */
    public ?int $seq = null;

    /**
     *
     * @ORM\Column(name="dt_carta_correcao", type="datetime", nullable=false)
     * @var null|DateTime
     */
    public ?DateTime $dtCartaCorrecao = null;

    /**
     * @NotUppercase()
     * @ORM\Column(name="msg_retorno", type="string", nullable=true)
     * @var null|string
     */
    public ?string $msgRetorno = null;

}