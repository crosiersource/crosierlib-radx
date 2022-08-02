<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ApiResource(
 *     shortName="Saldo",
 *     normalizationContext={"groups"={"saldo","carteira","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"saldo"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/saldo/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/saldo/{id}", "security"="is_granted('__NINGUEM__')"},
 *          "delete"={"path"="/fin/saldo/{id}", "security"="is_granted('__NINGUEM__')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/saldo", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/saldo", "security"="is_granted('__NINGUEM__')"}
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
 * @ApiFilter(SearchFilter::class,
 *     properties={
 *     "id": "exact",
 *     "carteira": "exact",
 *     "carteira.codigo": "exact",
 *     "dtSaldo": "exact"
 * })
 *
 * @ApiFilter(DateFilter::class, properties={"dtSaldo"})
 *
 * @ApiFilter(RangeFilter::class, properties={"valor"})
 *
 * @ApiFilter(OrderFilter::class, properties={
 *     "id",
 *     "dtSaldo",
 *     "valor",
 *     "carteira.codigo",
 *     "updated"
 * }, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\SaldoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\SaldoRepository")
 * @ORM\Table(name="fin_saldo")
 *
 * @author Carlos Eduardo Pauluk
 */
class Saldo implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_id")
     * @Groups("saldo")
     */
    public ?Carteira $carteira = null;

    /**
     * @ORM\Column(name="dt_saldo", type="datetime")
     * @Groups("saldo")
     */
    public ?DateTime $dtSaldo = null;

    /**
     * @ORM\Column(name="total_realizadas", type="decimal", precision=15, scale=2)
     * @Groups("saldo")
     */
    public ?string $totalRealizadas = null;

    /**
     * @ORM\Column(name="total_pendencias", type="decimal", precision=15, scale=2)
     * @Groups("saldo")
     */
    public ?string $totalPendencias = null;
    
    
    


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("saldo")
     * @SerializedName("totalRealizadas")
     * @return float
     */
    public function getTotalRealizadasFormatted(): float
    {
        return (float)$this->totalRealizadas;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("saldo")
     * @SerializedName("totalRealizadas")
     * @param float $totalRealizadas
     */
    public function setTotalRealizadasFormatted(float $totalRealizadas)
    {
        $this->totalRealizadas = $totalRealizadas;
    }



    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("saldo")
     * @SerializedName("totalPendencias")
     * @return float
     */
    public function getTotalPendenciasFormatted(): float
    {
        return (float)$this->totalPendencias;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("saldo")
     * @SerializedName("totalPendencias")
     * @param float $totalPendencias
     */
    public function setTotalPendenciasFormatted(float $totalPendencias)
    {
        $this->totalPendencias = $totalPendencias;
    }


    /**
     * @Groups("saldo")
     * @SerializedName("totalComPendentes")
     * @return float
     */
    public function getTotalComPendentes(): float
    {
        return (float)(bcsub($this->getTotalRealizadasFormatted(), $this->getTotalPendenciasFormatted(), 2));
    }



}

