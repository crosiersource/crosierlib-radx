<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Não é um endpoint normal. Verificar o SaldoController.
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

