<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidade Regra de Importação de Linha.
 * Configura uma regra para setar corretamente a Movimentação ao importar uma linha de extrato.
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\RegraImportacaoLinhaRepository")
 * @ORM\Table(name="fin_regra_import_linha")
 *
 * @author Carlos Eduardo Pauluk
 */
class RegraImportacaoLinha implements EntityId
{

    use EntityIdTrait;

    /**
     * Em casos especiais (como na utilização de named groups) posso usar uma regex em java.
     *
     * @ORM\Column(name="regra_regex_java", type="string")
     * @Groups("entity")
     */
    public ?string $regraRegexJava = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\TipoLancto")
     * @ORM\JoinColumn(name="tipo_lancto_id", nullable=true)
     * @Groups("entity")
     */
    public ?TipoLancto $tipoLancto = null;

    /**
     * @ORM\Column(name="status", type="string")
     * @Groups("entity")
     */
    public ?string $status = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_id", nullable=true)
     * @Groups("entity")
     */
    public ?Carteira $carteira = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_destino_id", nullable=true)
     * @Groups("entity")
     */
    public ?Carteira $carteiraDestino = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CentroCusto")
     * @ORM\JoinColumn(name="centrocusto_id", nullable=true)
     * @Groups("entity")
     */
    public ?CentroCusto $centroCusto = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo")
     * @ORM\JoinColumn(name="modo_id", nullable=true)
     * @Groups("entity")
     */
    public ?Modo $modo = null;

    /**
     * @NotUppercase()
     * @ORM\Column(name="padrao_descricao", type="string")
     * @Groups("entity")
     */
    public ?string $padraoDescricao = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria")
     * @ORM\JoinColumn(name="categoria_id", nullable=true)
     * @Groups("entity")
     */
    public ?Categoria $categoria = null;

    /**
     * Para poder aplicar a regra somente se for positivo (1), negativo (-1) ou ambos (0)
     *
     * @ORM\Column(name="sinal_valor", type="integer")
     * @Groups("entity")
     */
    public ?int $sinalValor = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Banco")
     * @ORM\JoinColumn(name="cheque_banco_id", nullable=true)
     * @Groups("entity")
     */
    public ?Banco $chequeBanco = null;

    /**
     * Código da agência (sem o dígito verificador).
     *
     * @ORM\Column(name="cheque_agencia", type="string")
     * @Groups("entity")
     */
    public ?string $chequeAgencia = null;

    /**
     * Número da conta no banco (não segue um padrão).
     *
     * @ORM\Column(name="cheque_conta", type="string")
     * @Groups("entity")
     */
    public ?string $chequeConta = null;

    /**
     * Número da conta no banco (não segue um padrão).
     *
     * @ORM\Column(name="cheque_num_cheque", type="string")
     * @Groups("entity")
     */
    public ?string $chequeNumCheque = null;


    public function getSinalValorLabel()
    {
        switch ($this->sinalValor) {
            case 0:
                return 'Ambos';
            case 1:
                return 'Positivo';
            case -1:
                return 'Negativo';
            default:
                return null;
        }
    }


}
