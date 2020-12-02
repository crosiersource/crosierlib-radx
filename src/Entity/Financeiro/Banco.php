<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entidade 'Banco'.
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\BancoRepository")
 * @ORM\Table(name="fin_banco")
 *
 * @author Carlos Eduardo Pauluk
 */
class Banco implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="codigo_banco", type="integer", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Range(min = 1)
     * @Groups("entity")
     */
    public ?int $codigoBanco = null;

    /**
     * @ORM\Column(name="nome", type="string", nullable=false, length=200)
     * @Assert\NotBlank()
     * @Groups("entity")
     */
    public ?string $nome = null;

    /**
     * @ORM\Column(name="utilizado", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("entity")
     */
    public ?bool $utilizado = false;


    /**
     * @param bool $format
     * @return int|null
     */
    public function getCodigoBanco(bool $format = false)
    {
        if ($format) {
            return str_pad($this->codigoBanco, 3, '0', STR_PAD_LEFT);
        }

        return $this->codigoBanco;
    }

    /**
     * @return string
     * @Groups("entity")
     */
    public function getDescricaoMontada(): string
    {
        return $this->getCodigoBanco(true) . ' - ' . $this->nome;
    }

}

