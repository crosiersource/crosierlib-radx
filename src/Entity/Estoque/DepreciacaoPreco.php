<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\DepreciacaoPrecoRepository")
 * @ORM\Table(name="est_depreciacao_preco")
 *
 * @author Carlos Eduardo Pauluk
 */
class DepreciacaoPreco implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="porcentagem", type="decimal", nullable=false)
     * @Groups("entity")
     */
    private ?float $porcentagem;

    /**
     * @ORM\Column(name="prazo_fim", type="integer", nullable=false)
     * @Groups("entity")
     */
    private ?int $prazoFim;

    /**
     * @ORM\Column(name="prazo_ini", type="integer", nullable=false)
     * @Groups("entity")
     */
    private ?int $prazoIni;

}