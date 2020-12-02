<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Entidade 'Cadeia de Movimentações'.
 *
 * Movimentações podem ser dependentes umas das outras, formando uma cadeia de entradas e saídas entre carteiras.
 *
 * @ORM\Entity()
 * @ORM\Table(name="fin_cadeia")
 *
 * @author Carlos Eduardo Pauluk
 */
class Cadeia implements EntityId
{

    use EntityIdTrait;

    /**
     * Se for vinculante, ao deletar uma movimentação da cadeia todas deverão são deletadas (ver trigger trg_ad_delete_cadeia).
     *
     * @ORM\Column(name="vinculante", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $vinculate = false;

    /**
     * Se for fechada, não é possível incluir outras movimentações na cadeia.
     *
     * @ORM\Column(name="fechada", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $fechada = false;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Movimentacao",
     *      mappedBy="cadeia",
     *      orphanRemoval=true
     * )
     *
     * @var Movimentacao[]|ArrayCollection|null
     */
    public $movimentacoes;


    public function __construct()
    {
        $this->movimentacoes = new ArrayCollection();
    }


}

