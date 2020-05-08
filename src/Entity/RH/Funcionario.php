<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\RH;

use CrosierSource\CrosierLibBaseBundle\Entity\Base\Pessoa;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\RH\FuncionarioRepository")
 * @ORM\Table(name="rh_funcionario")
 *
 * @author Carlos Eduardo Pauluk
 */
class Funcionario implements EntityId
{

    use EntityIdTrait;


    /**
     *
     * @ORM\Column(name="codigo", type="integer", nullable=false)
     * @var null|int
     *
     * @Groups("entity")
     */
    public ?int $codigo;

    /**
     *
     * @ORM\Column(name="nome_ekt", type="string", nullable=true, length=200)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $nomeEkt;

    /**
     *
     * @ORM\Column(name="clt", type="boolean", nullable=false)
     * @var null|bool
     *
     * @Groups("entity")
     */
    public ?bool $clt;

    /**
     *
     * @ORM\Column(name="dt_nascimento", type="datetime", nullable=true)
     * @var null|\DateTime
     *
     * @Groups("entity")
     */
    public ?\DateTime $dtNascimento;

    /**
     *
     * @ORM\Column(name="email", type="string", nullable=true, length=50)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $email;

    /**
     *
     * @ORM\Column(name="estado_civil", type="string", nullable=true, length=13)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $estadoCivil;

    /**
     *
     * @ORM\Column(name="fone1", type="string", nullable=true, length=15)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $fone1;

    /**
     *
     * @ORM\Column(name="fone2", type="string", nullable=true, length=15)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $fone2;

    /**
     *
     * @ORM\Column(name="fone3", type="string", nullable=true, length=15)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $fone3;

    /**
     *
     * @ORM\Column(name="fone4", type="string", nullable=true, length=15)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $fone4;

    /**
     *
     * @ORM\Column(name="naturalidade", type="string", nullable=true, length=50)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $naturalidade;

    /**
     *
     * @ORM\Column(name="rg", type="string", nullable=true, length=15)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $rg;

    /**
     *
     * @ORM\Column(name="senha", type="string", nullable=true, length=200)
     * @var null|string
     */
    public ?string $senha;

    /**
     *
     * @ORM\Column(name="vendedor_comissionado", type="boolean", nullable=false)
     * @var null|bool
     *
     * @Groups("entity")
     */
    public ?bool $vendedorComissionado;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibBaseBundle\Entity\Base\Pessoa")
     * @ORM\JoinColumn(name="pessoa_id", nullable=false)
     * @Groups("entity")
     *
     * @var null|Pessoa
     */
    public ?Pessoa $pessoa;

    /**
     *
     * @ORM\Column(name="dt_emissao_rg", type="datetime", nullable=true)
     *
     * @var null|\DateTime
     *
     * @Groups("entity")
     */
    public ?\DateTime $dtEmissaoRg;

    /**
     *
     * @ORM\Column(name="estado_rg", type="string", nullable=true, length=2)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $estadoRg;

    /**
     *
     * @ORM\Column(name="orgao_emissor_rg", type="string", nullable=true, length=15)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $orgaoEmissorRg;

    /**
     *
     * @ORM\Column(name="sexo", type="string", nullable=true, length=9)
     * @var null|string
     *
     * @Groups("entity")
     */
    public ?string $sexo;

    /**
     *
     * @ORM\Column(name="dt_admissao", type="datetime", nullable=true)
     * @var null|\DateTime
     *
     * @Groups("entity")
     */
    public ?\DateTime $dtAdmissao;

    /**
     *
     * @ORM\Column(name="dt_demissao", type="datetime", nullable=true)
     * @var null|\DateTime
     *
     * @Groups("entity")
     */
    public ?\DateTime $dtDemissao;

    /**
     *
     * @var null|FuncionarioCargo[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="FuncionarioCargo",
     *      mappedBy="funcionario",
     *      orphanRemoval=true
     * )
     */
    public $cargos;


    /**
     * @return bool|null
     */
    public function getClt(): ?bool
    {
        return $this->clt;
    }

    /**
     * @param bool|null $clt
     * @return Funcionario
     */
    public function setClt(?bool $clt): Funcionario
    {
        $this->clt = $clt;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCodigo(): ?int
    {
        return $this->codigo;
    }

    /**
     * @param int|null $codigo
     * @return Funcionario
     */
    public function setCodigo(?int $codigo): Funcionario
    {
        $this->codigo = $codigo;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDtNascimento(): ?\DateTime
    {
        return $this->dtNascimento;
    }

    /**
     * @param \DateTime|null $dtNascimento
     * @return Funcionario
     */
    public function setDtNascimento(?\DateTime $dtNascimento): Funcionario
    {
        $this->dtNascimento = $dtNascimento;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param null|string $email
     * @return Funcionario
     */
    public function setEmail(?string $email): Funcionario
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getEstadoCivil(): ?string
    {
        return $this->estadoCivil;
    }

    /**
     * @param null|string $estadoCivil
     * @return Funcionario
     */
    public function setEstadoCivil(?string $estadoCivil): Funcionario
    {
        $this->estadoCivil = $estadoCivil;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getFone1(): ?string
    {
        return $this->fone1;
    }

    /**
     * @param null|string $fone1
     * @return Funcionario
     */
    public function setFone1(?string $fone1): Funcionario
    {
        $this->fone1 = $fone1;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getFone2(): ?string
    {
        return $this->fone2;
    }

    /**
     * @param null|string $fone2
     * @return Funcionario
     */
    public function setFone2(?string $fone2): Funcionario
    {
        $this->fone2 = $fone2;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getFone3(): ?string
    {
        return $this->fone3;
    }

    /**
     * @param null|string $fone3
     * @return Funcionario
     */
    public function setFone3(?string $fone3): Funcionario
    {
        $this->fone3 = $fone3;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getFone4(): ?string
    {
        return $this->fone4;
    }

    /**
     * @param null|string $fone4
     * @return Funcionario
     */
    public function setFone4(?string $fone4): Funcionario
    {
        $this->fone4 = $fone4;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getNaturalidade(): ?string
    {
        return $this->naturalidade;
    }

    /**
     * @param null|string $naturalidade
     * @return Funcionario
     */
    public function setNaturalidade(?string $naturalidade): Funcionario
    {
        $this->naturalidade = $naturalidade;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getNomeEkt(): ?string
    {
        return $this->nomeEkt;
    }

    /**
     * @param null|string $nomeEkt
     * @return Funcionario
     */
    public function setNomeEkt(?string $nomeEkt): Funcionario
    {
        $this->nomeEkt = $nomeEkt;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getRg(): ?string
    {
        return $this->rg;
    }

    /**
     * @param null|string $rg
     * @return Funcionario
     */
    public function setRg(?string $rg): Funcionario
    {
        $this->rg = $rg;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getSenha(): ?string
    {
        return $this->senha;
    }

    /**
     * @param null|string $senha
     * @return Funcionario
     */
    public function setSenha(?string $senha): Funcionario
    {
        $this->senha = $senha;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getVendedorComissionado(): ?bool
    {
        return $this->vendedorComissionado;
    }

    /**
     * @param bool|null $vendedorComissionado
     * @return Funcionario
     */
    public function setVendedorComissionado(?bool $vendedorComissionado): Funcionario
    {
        $this->vendedorComissionado = $vendedorComissionado;
        return $this;
    }

    /**
     * @return Pessoa|null
     */
    public function getPessoa(): ?Pessoa
    {
        return $this->pessoa;
    }

    /**
     * @param Pessoa|null $pessoa
     * @return Funcionario
     */
    public function setPessoa(?Pessoa $pessoa): Funcionario
    {
        $this->pessoa = $pessoa;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDtEmissaoRg(): ?\DateTime
    {
        return $this->dtEmissaoRg;
    }

    /**
     * @param \DateTime|null $dtEmissaoRg
     * @return Funcionario
     */
    public function setDtEmissaoRg(?\DateTime $dtEmissaoRg): Funcionario
    {
        $this->dtEmissaoRg = $dtEmissaoRg;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getEstadoRg(): ?string
    {
        return $this->estadoRg;
    }

    /**
     * @param null|string $estadoRg
     * @return Funcionario
     */
    public function setEstadoRg(?string $estadoRg): Funcionario
    {
        $this->estadoRg = $estadoRg;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getOrgaoEmissorRg(): ?string
    {
        return $this->orgaoEmissorRg;
    }

    /**
     * @param null|string $orgaoEmissorRg
     * @return Funcionario
     */
    public function setOrgaoEmissorRg(?string $orgaoEmissorRg): Funcionario
    {
        $this->orgaoEmissorRg = $orgaoEmissorRg;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getSexo(): ?string
    {
        return $this->sexo;
    }

    /**
     * @param null|string $sexo
     * @return Funcionario
     */
    public function setSexo(?string $sexo): Funcionario
    {
        $this->sexo = $sexo;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDtAdmissao(): ?\DateTime
    {
        return $this->dtAdmissao;
    }

    /**
     * @param \DateTime|null $dtAdmissao
     * @return Funcionario
     */
    public function setDtAdmissao(?\DateTime $dtAdmissao): Funcionario
    {
        $this->dtAdmissao = $dtAdmissao;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDtDemissao(): ?\DateTime
    {
        return $this->dtDemissao;
    }

    /**
     * @param \DateTime|null $dtDemissao
     * @return Funcionario
     */
    public function setDtDemissao(?\DateTime $dtDemissao): Funcionario
    {
        $this->dtDemissao = $dtDemissao;
        return $this;
    }

    /**
     * @return FuncionarioCargo[]|ArrayCollection|null
     */
    public function getCargos()
    {
        return $this->cargos;
    }

    /**
     * @param FuncionarioCargo[]|ArrayCollection|null $cargos
     * @return Funcionario
     */
    public function setCargos($cargos)
    {
        $this->cargos = $cargos;
        return $this;
    }


}
