<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidade Bandeira de Cartão.
 * Ex.: MASTER MAESTRO, MASTER, VISA ELECTRON, VISA, etc.
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\BandeiraCartaoRepository")
 * @ORM\Table(name="fin_bandeira_cartao")
 *
 * @author Carlos Eduardo Pauluk
 */
class BandeiraCartao implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
     */
    public ?string $descricao = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("entity")
     */
    public ?Modo $modo = null;

    /**
     * Para marcar diferentes nomes que podem ser utilizados para definir uma bandeira (ex.: MAESTRO ou MASTER MAESTRO ou M MAESTRO).
     *
     * @ORM\Column(name="labels", type="string")
     * @Groups("entity")
     */
    public ?string $labels = null;


}
