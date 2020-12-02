<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\RH;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\RH\CargoRepository")
 * @ORM\Table(name="rh_cargo")
 */
class Cargo implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="descricao", type="string", nullable=false, length=200)
     * @Groups("entity")
     */
    private ?string $descricao;

}
    
