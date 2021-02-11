<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * Entidade 'Movimentação'.
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\MovimentacaoRepository")
 * @ORM\Table(name="fin_movimentacao")
 *
 * @author Carlos Eduardo Pauluk
 */
class Movimentacao implements EntityId
{

    use EntityIdTrait;

    /**
     * Utilizado, por exemplo, na importação (para tratar duplicidades).
     * @Groups("entity")
     * @ORM\Column(name="uuid", type="string")
     * @NotUppercase()
     */
    public ?string $UUID = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Fatura", inversedBy="movimentacoes")
     * @ORM\JoinColumn(name="fatura_id")
     * @Groups("entity")
     * @MaxDepth(2)
     */
    public ?Fatura $fatura = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo")
     * @ORM\JoinColumn(name="modo_id")
     * @Groups("entity")
     */
    public ?Modo $modo = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Banco")
     * @ORM\JoinColumn(name="documento_banco_id")
     * @Groups("entity")
     */
    public ?Banco $documentoBanco = null;

    /**
     * @ORM\Column(name="documento_num", type="string", length=200)
     * @Groups("entity")
     */
    public ?string $documentoNum = null;

    /**
     * CPF/CNPJ de quem paga esta movimentação.
     *
     * @ORM\Column(name="sacado", type="string")
     * @Groups("entity")
     */
    public ?string $sacado = null;

    /**
     * CPF/CNPJ de quem recebe esta movimentação.
     *
     * @ORM\Column(name="cedente", type="string")
     * @Groups("entity")
     */
    public ?string $cedente = null;

    /**
     * @ORM\Column(name="quitado", type="boolean")
     * @Groups("entity")
     */
    public ?bool $quitado = false;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\TipoLancto")
     * @ORM\JoinColumn(name="tipo_lancto_id")
     * @Groups("entity")
     */
    public ?TipoLancto $tipoLancto = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_id")
     * @Groups("entity")
     * @MaxDepth(2)
     */
    public ?Carteira $carteira = null;

    /**
     * Carteira informada em casos de TRANSF_PROPRIA.
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_destino_id")
     * @Groups("entity")
     * @MaxDepth(2)
     */
    public ?Carteira $carteiraDestino = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria")
     * @ORM\JoinColumn(name="categoria_id")
     * @Groups("entity")
     * @MaxDepth(2)
     */
    public ?Categoria $categoria = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CentroCusto")
     * @ORM\JoinColumn(name="centrocusto_id")
     * @Groups("entity")
     */
    public ?CentroCusto $centroCusto = null;

    /**
     * Caso seja uma movimentação agrupada em um Grupo de Movimentação (item).
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem", inversedBy="movimentacoes")
     * @ORM\JoinColumn(name="grupo_item_id")
     * @Groups("entity")
     * @MaxDepth(2)
     */
    public ?GrupoItem $grupoItem = null;

    /**
     * @ORM\Column(name="status", type="string", length=50)
     * @Groups("entity")
     */
    public ?string $status = null;

    /**
     * @ORM\Column(name="descricao", type="string", length=500)
     * @Groups("entity")
     */
    public ?string $descricao = null;


    /**
     * Data em que a movimentação efetivamente aconteceu.
     *
     * @ORM\Column(name="dt_moviment", type="datetime")
     * @Groups("entity")
     */
    public ?\DateTime $dtMoviment = null;

    /**
     * Data prevista para pagamento.
     *
     * @ORM\Column(name="dt_vencto", type="datetime")
     * @Groups("entity")
     */
    public ?\DateTime $dtVencto = null;

    /**
     * Data prevista (postergando para dia útil) para pagamento.
     *
     * @ORM\Column(name="dt_vencto_efetiva", type="datetime")
     * @Groups("entity")
     */
    public ?\DateTime $dtVenctoEfetiva = null;

    /**
     * Data em que a movimentação foi paga.
     *
     * @ORM\Column(name="dt_pagto", type="datetime")
     * @Groups("entity")
     */
    public ?\DateTime $dtPagto = null;

    /**
     * Se dtPagto != null ? dtPagto : dtVencto.
     *
     * @ORM\Column(name="dt_util", type="datetime")
     * @Groups("entity")
     */
    public ?\DateTime $dtUtil = null;


    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Banco")
     * @ORM\JoinColumn(name="cheque_banco_id")
     * @Groups("entity")
     */
    public ?Banco $chequeBanco = null;

    /**
     * Código da agência (sem o dígito verificador).
     *
     * @ORM\Column(name="cheque_agencia", type="string", length=30)
     * @Groups("entity")
     */
    public ?string $chequeAgencia = null;

    /**
     * Número da conta no banco (não segue um padrão).
     *
     * @ORM\Column(name="cheque_conta", type="string", length=30)
     * @Groups("entity")
     */
    public ?string $chequeConta = null;

    /**
     * @ORM\Column(name="cheque_num_cheque", type="string", length=30)
     * @Groups("entity")
     */
    public ?string $chequeNumCheque = null;


    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\OperadoraCartao")
     * @ORM\JoinColumn(name="operadora_cartao_id")
     * @Groups("entity")
     * @MaxDepth(2)
     */
    public ?OperadoraCartao $operadoraCartao = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\BandeiraCartao")
     * @ORM\JoinColumn(name="bandeira_cartao_id")
     * @Groups("entity")
     */
    public ?BandeiraCartao $bandeiraCartao = null;

    /**
     *
     * @ORM\Column(name="qtde_parcelas_cartao", type="integer")
     * @Groups("entity")
     */
    public ?int $qtdeParcelasCartao = null;

    /**
     * Geralmente o NSU.
     *
     * @ORM\Column(name="id_transacao_cartao", type="string")
     * @Groups("entity")
     */
    public ?string $idTransacaoCartao = null;

    /**
     * Número do cartão, geralmente identificado como: **** **** **** 1234
     * @ORM\Column(name="num_cartao", type="string")
     * @Groups("entity")
     */
    public ?string $numCartao = null;


    /**
     * @ORM\Column(name="recorrente", type="boolean")
     * @Groups("entity")
     */
    public ?bool $recorrente = false;

    /**
     * @ORM\Column(name="recorr_frequencia", type="string", length=50)
     * @Groups("entity")
     */
    public ?string $recorrFrequencia = null;

    /**
     * @ORM\Column(name="recorr_tipo_repet", type="string", length=50)
     * @Groups("entity")
     */
    public ?string $recorrTipoRepet = null;

    /**
     * Utilizar 32 para marcar o último dia do mês.
     *
     * @ORM\Column(name="recorr_dia", type="integer")
     * @Groups("entity")
     */
    public ?int $recorrDia = null;

    /**
     * Utilizado para marcar a variação em relação ao dia em que seria o vencimento.
     * Exemplo: dia=32 (último dia do mês) + variacao=-2 >>> 2 dias antes do último dia do mês
     *
     * @ORM\Column(name="recorr_variacao", type="integer")
     * @Groups("entity")
     */
    public ?int $recorrVariacao = null;


    /**
     * Valor bruto da movimentação.
     *
     * @ORM\Column(name="valor", type="decimal", precision=15, scale=2)
     * @Groups("entity")
     */
    public ?float $valor = null;

    /**
     * Possíveis descontos (sempre negativo).
     *
     * @ORM\Column(name="descontos", type="decimal", precision=15, scale=2)
     * @Groups("entity")
     */
    public ?float $descontos = null;

    /**
     * Possíveis acréscimos (sempre positivo).
     *
     * @ORM\Column(name="acrescimos", type="decimal", precision=15, scale=2)
     * @Groups("entity")
     */
    public ?float $acrescimos = null;

    /**
     * Valor total informado no campo e que é salvo no banco (pode divergir da
     * conta por algum motivo).
     *
     * @ORM\Column(name="valor_total", type="decimal", precision=15, scale=2)
     * @Groups("entity")
     */
    public ?float $valorTotal = null;


    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Cadeia", inversedBy="movimentacoes")
     * @ORM\JoinColumn(name="cadeia_id", referencedColumnName="id")
     * @Groups("entity")
     * @MaxDepth(2)
     */
    public ?Cadeia $cadeia = null;

    /**
     * @ORM\Column(name="parcelamento", type="boolean")
     * @Groups("entity")
     */
    public ?bool $parcelamento = false;

    /**
     * Caso a movimentação faça parte de uma cadeia, informa em qual posição.
     * Também é utilizado para armazenar o número da parcela.
     *
     * @ORM\Column(name="cadeia_ordem", type="integer")
     * @Groups("entity")
     */
    public ?int $cadeiaOrdem = null;

    /**
     * Informa o total de movimentações na cadeia. Campo apenas auxiliar.
     * Obs.: não pode nunca ser 1.
     *
     * @ORM\Column(name="cadeia_qtde", type="integer")
     * @Groups("entity")
     */
    public ?int $cadeiaQtde = null;

    /**
     * @ORM\Column(name="obs", type="string", length=5000)
     * @Groups("entity")
     */
    public ?string $obs = null;

    /**
     * @ORM\Column(name="json_data", type="json")
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;


    /**
     * @Groups("entity")
     * @return string
     */
    public function getDescricaoMontada(): string
    {
        $sufixo = '';

        if ($this->cadeia && $this->parcelamento) {
            $qtdeParcelas = $this->cadeia->movimentacoes->count();
            $zerosfill = strlen('' . $qtdeParcelas);
            $zerosfill = $zerosfill < 2 ? 2 : $zerosfill;
            $sufixo .= ' (' . str_pad($this->cadeiaOrdem, $zerosfill, '0', STR_PAD_LEFT) . '/' . str_pad($qtdeParcelas, $zerosfill, '0', STR_PAD_LEFT) . ')';
        }

        if ($this->documentoNum) {
            $sufixo .= ' (Doc: ' . $this->documentoNum . ')';
        }

        if ($this->chequeNumCheque) {
            $nomeBanco = '';
            if ($this->chequeBanco) {
                $nomeBanco = $this->chequeBanco->nome . ' - ';
            }
            $sufixo .= '<br /> (CHQ: ' . $nomeBanco . 'nº ' . $this->chequeNumCheque . ')';
        }

        if ($this->bandeiraCartao) {
            $sufixo .= ' (Bandeira: ' . $this->bandeiraCartao->descricao . ')';
        }

        if ($this->operadoraCartao) {
            $sufixo .= ' (Operadora: ' . $this->operadoraCartao->descricao . ')';
        }

        if ($this->grupoItem) {
            $sufixo .= ' (' . $this->grupoItem->descricao . ')';
        }

        return $this->descricao . $sufixo;
    }


    /**
     * Calcula e seta o valor total.
     */
    public function calcValorTotal(): void
    {
        $valorTotal = $this->valor + $this->descontos + $this->acrescimos;
        $this->valorTotal = $valorTotal;
    }


    /**
     * Retorna as outras movimentações que fazem parte da mesma cadeia desta.
     *
     * @return array
     */
    public function getOutrasMovimentacoesDaCadeia(): array
    {
        $outrasMovs = [];
        if ($this->cadeia) {
            foreach ($this->cadeia->movimentacoes as $outraMov) {
                if ($outraMov->getId() !== $this->getId()) {
                    $outrasMovs[] = $outraMov;
                }
            }
        }
        return $outrasMovs;
    }

    /**
     * @return bool
     * @Groups("entity")
     */
    public function isTransferenciaEntreCarteiras(): bool
    {
        return
            $this->cadeia &&
            $this->cadeia->movimentacoes &&
            $this->cadeia->movimentacoes->count() === 2 &&
            $this->categoria &&
            in_array($this->categoria->codigo, [199, 299], true);
    }

    /**
     * @return bool
     * @Groups("entity")
     */
    public function isTransferenciaEntradaCaixa(): bool
    {
        return
            $this->cadeia &&
            $this->cadeia->movimentacoes &&
            $this->cadeia->movimentacoes->count() === 3 &&
            $this->categoria &&
            in_array($this->categoria->codigo, [101, 102, 199, 299], true);
    }

    /**
     * @return bool
     * @Groups("entity")
     */
    public function isUltimaNaCadeia(): bool
    {
        return
            $this->cadeia &&
            $this->cadeia->movimentacoes &&
            $this->cadeia->movimentacoes->count() === $this->cadeiaOrdem;
    }

    /**
     * Nos casos das movimentações entre carteiras 1.99 ou 2.99...
     * @Groups("entity")
     * @MaxDepth(2)
     *
     * @return null|Movimentacao
     */
    public function getMovimentacaoOposta(): ?Movimentacao
    {
        if ($this->isTransferenciaEntreCarteiras()) {
            return $this->getOutrasMovimentacoesDaCadeia()[0];
        }
        return null;
    }
}

