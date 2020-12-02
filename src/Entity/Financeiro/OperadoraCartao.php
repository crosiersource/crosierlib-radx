<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entidade Operadora de Cartões.
 * Ex.: RDCARD, CIELO, STONE.
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\OperadoraCartaoRepository")
 * @ORM\Table(name="fin_operadora_cartao")
 *
 * @author Carlos Eduardo Pauluk
 */
class OperadoraCartao implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="descricao", type="string", nullable=false, length=40)
     * @Assert\NotBlank()
     * @Groups("entity")
     */
    public ?string $descricao = null;

    /**
     * Em qual Carteira as movimentações desta Operadora acontecem.
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_id", nullable=true)
     * @Groups("entity")
     */
    public ?Carteira $carteira = null;

}
