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
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidade que guarda informações sobre o histórico da nota fiscal.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"notaFiscalHistorico","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"notaFiscalHistorico"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/notaFiscalHistorico/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "put"={"path"="/fis/notaFiscalHistorico/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "delete"={"path"="/fis/notaFiscalHistorico/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/notaFiscalHistorico", "security"="is_granted('ROLE_FISCAL')"},
 *          "post"={"path"="/fis/notaFiscalHistorico", "security"="is_granted('ROLE_FISCAL')"}
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
 *      "id": "exact",
 *      "notaFiscal": "exact"
 *  })
 *
 * @ApiFilter(OrderFilter::class, properties={
 *     "id",
 *     "updated",
 *     "dtHistorico"
 * }, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalHistoricoEntityHandler")
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalHistoricoRepository")
 * @ORM\Table(name="fis_nf_historico")
 *
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalHistorico implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal")
     * @ORM\JoinColumn(name="fis_nf_id")
     * @Groups("notaFiscalHistorico")
     * @var $notaFiscal null|NotaFiscal
     */
    public ?NotaFiscal $notaFiscal = null;

    /**
     * @ORM\Column(name="codigo_status", type="integer", nullable=false)
     * @Groups("notaFiscalHistorico")
     * @var null|int
     */
    public ?int $codigoStatus = null;

    /**
     * @ORM\Column(name="dt_historico", type="datetime", nullable=false)
     * @Groups("notaFiscalHistorico")
     * @var null|DateTime
     */
    public ?DateTime $dtHistorico = null;

    /**
     * @ORM\Column(name="descricao", type="string", nullable=false, length=2000)
     * @Groups("notaFiscalHistorico")
     * @var null|string
     */
    public ?string $descricao = null;

    /**
     * @ORM\Column(name="obs", type="string", nullable=false, length=255)
     * @Groups("notaFiscalHistorico")
     * @var null|string
     * @NotUppercase()
     */
    public ?string $obs = null;


}