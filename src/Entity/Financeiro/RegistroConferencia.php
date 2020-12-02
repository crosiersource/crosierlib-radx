<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidade para manter registros de conferências mensais.
 *
 * @author Carlos Eduardo Pauluk
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\RegistroConferenciaRepository")
 * @ORM\Table(name="fin_reg_conf")
 */
class RegistroConferencia implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
     */
    public ?string $descricao = null;

    /**
     * @ORM\Column(name="dt_registro", type="datetime")
     * @Groups("entity")
     */
    public ?\Datetime $dtRegistro = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_id", nullable=true)
     * @Groups("entity")
     */
    public ?Carteira $carteira = null;

    /**
     * @ORM\Column(name="valor", type="decimal")
     * @Groups("entity")
     */
    public ?float $valor = null;

    /**
     * @ORM\Column(name="obs", type="string")
     * @Groups("entity")
     */
    public ?string $obs = null;


}
