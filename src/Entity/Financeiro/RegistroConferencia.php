<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Datetime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidade para manter registros de conferências mensais.
 *
 * @ApiResource(
*     normalizationContext={"groups"={"registroConferencia","carteira","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"registroConferencia"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/registroConferencia/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/registroConferencia/{id}", "security"="is_granted('ROLE_FINAN_ADMIN')"},
 *          "delete"={"path"="/fin/registroConferencia/{id}", "security"="is_granted('ROLE_FINAN_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/registroConferencia", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/registroConferencia", "security"="is_granted('ROLE_FINAN_ADMIN')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 *
 * )
 * @ApiFilter(PropertyFilter::class)
 * 
 * @ApiFilter(DateFilter::class, properties={"dtRegistro"})
 * 
 * @ApiFilter(RangeFilter::class, properties={"valor"})
 *
 * @ApiFilter(SearchFilter::class,
 *     properties={
 *     "id": "exact",
 *     "descricao": "partial",
 *     "carteira": "exact"
 * })
 * @ApiFilter(OrderFilter::class, properties={"id", "descricao", "dtVencto", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\RegistroConferenciaEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\RegistroConferenciaRepository")
 * @ORM\Table(name="fin_reg_conf")
 *
 * @author Carlos Eduardo Pauluk
 */
class RegistroConferencia implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="descricao", type="string")
     * @Groups("registroConferencia")
     */
    public ?string $descricao = null;

    /**
     * @ORM\Column(name="dt_registro", type="datetime")
     * @Groups("registroConferencia")
     */
    public ?Datetime $dtRegistro = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_id", nullable=true)
     * @Groups("registroConferencia")
     */
    public ?Carteira $carteira = null;

    /**
     * @ORM\Column(name="valor", type="decimal")
     * @Groups("n")
     */
    public ?float $valor = null;

    /**
     * @ORM\Column(name="obs", type="string")
     * @Groups("registroConferencia")
     */
    public ?string $obs = null;


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("registroConferencia")
     * @SerializedName("valor")
     * @return float
     */
    public function getValorFormatted(): float
    {
        return (float)$this->valor;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("registroConferencia")
     * @SerializedName("valor")
     * @param float $valor
     */
    public function setValorFormatted(float $valor)
    {
        $this->valor = $valor;
    }



}
