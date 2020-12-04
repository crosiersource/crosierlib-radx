<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * Entidade 'Fatura'.
 *
 * Agrupa diversas movimentações que são pagas com referência a um documento fiscal.
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\FaturaRepository")
 * @ORM\Table(name="fin_fatura")
 *
 * @author Carlos Eduardo Pauluk
 */
class Fatura implements EntityId
{

    use EntityIdTrait;

    /**
     * Data em que a movimentação efetivamente aconteceu.
     *
     * @ORM\Column(name="dt_fatura", type="datetime")
     * @Groups("entity")
     *
     * @var \DateTime|null
     */
    public ?\DateTime $dtFatura = null;

    /**
     *
     * Se for fechada, não é possível incluir outras movimentações na fatura.
     *
     * @ORM\Column(name="fechada", type="boolean")
     * @Groups("entity")
     *
     * @var bool|null
     */
    public ?bool $fechada = false;

    /**
     *
     * A quitação de uma FATURA TRANSACIONAL se dá pela regra: somatório das movimentações 292 = valor da movimentação 291.
     *
     * @ORM\Column(name="transacional", type="boolean")
     * @Groups("entity")
     *
     * @var bool|null
     */
    public ?bool $transacional = false;

    /**
     *
     * @ORM\Column(name="quitada", type="boolean")
     * @Groups("entity")
     *
     * @var bool|null
     */
    public ?bool $quitada = false;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;

    /**
     *
     * @var Movimentacao[]|ArrayCollection|null
     *
     * @ORM\OneToMany(targetEntity="Movimentacao", mappedBy="fatura")
     */
    private $movimentacoes;


    public function __construct()
    {
        $this->movimentacoes = new ArrayCollection();
    }

    /**
     * @return Movimentacao[]|ArrayCollection|null
     */
    public function getMovimentacoes()
    {
        return $this->movimentacoes;
    }

    /**
     * @param Movimentacao[]|ArrayCollection|null $movimentacoes
     * @return Fatura
     */
    public function setMovimentacoes($movimentacoes): Fatura
    {
        $this->movimentacoes = $movimentacoes;
        return $this;
    }


}

