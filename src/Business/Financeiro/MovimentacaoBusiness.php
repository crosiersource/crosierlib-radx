<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Entity\Base\DiaUtil;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\Base\DiaUtilRepository;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\ExceptionUtils\ExceptionUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\NumberUtils\DecimalUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Cadeia;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Grupo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\OperadoraCartao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\TipoLancto;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\CadeiaEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\MovimentacaoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CarteiraRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\MovimentacaoRepository;
use Doctrine\ORM\EntityManagerInterface;
use NumberFormatter;
use Psr\Log\LoggerInterface;

/**
 * Class MovimentacaoBusiness
 *
 * @package CrosierSource\CrosierLibRadxBundle\Business\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class MovimentacaoBusiness
{

    private EntityManagerInterface $doctrine;

    private GrupoBusiness $grupoBusiness;

    private MovimentacaoEntityHandler $movimentacaoEntityHandler;

    private CadeiaEntityHandler $cadeiaEntityHandler;

    private LoggerInterface $logger;

    /**
     *
     * MovimentacaoBusiness constructor.
     *
     * @param EntityManagerInterface $doctrine
     * @param GrupoBusiness $grupoBusiness
     * @param MovimentacaoEntityHandler $movimentacaoEntityHandler
     * @param CadeiaEntityHandler $cadeiaEntityHandler
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $doctrine,
                                GrupoBusiness $grupoBusiness,
                                MovimentacaoEntityHandler $movimentacaoEntityHandler,
                                CadeiaEntityHandler $cadeiaEntityHandler,
                                LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->grupoBusiness = $grupoBusiness;
        $this->movimentacaoEntityHandler = $movimentacaoEntityHandler;
        $this->cadeiaEntityHandler = $cadeiaEntityHandler;
        $this->logger = $logger;
    }


    /**
     * @param $movs
     * @return float|null
     */
    public function somarMovimentacoes($movs): ?float
    {
        $total = 0.0;
        /** @var Movimentacao $m */
        foreach ($movs as $m) {
            $total = $m->categoria->codigoSuper === 1 ? $total + $m->valorTotal : $total - $m->valorTotal;
        }
        return $total;
    }

    /**
     * @param Movimentacao $movimentacao
     * @param int $qtdeParcelas
     * @param float $valor
     * @param \DateTime $dtPrimeiroVencto
     * @param bool $isValorTotal
     * @param array|null $parcelas
     */
    public function gerarParcelas(Movimentacao $movimentacao, int $qtdeParcelas, float $valor, \DateTime $dtPrimeiroVencto, bool $isValorTotal = true, array $parcelas = null): void
    {
        $valorParcela = $isValorTotal ? bcdiv($valor, $qtdeParcelas, 2) : $valor;
        $resto = $isValorTotal ? bcsub($valor, bcmul($valorParcela, $qtdeParcelas, 2), 2) : 0.0;

        $cadeia = new Cadeia();
        $movimentacao->cadeia = $cadeia;

        /** @var DiaUtilRepository $repoDiaUtil */
        $repoDiaUtil = $this->doctrine->getRepository(DiaUtil::class);

        if (isset($parcelas[0])) {
            $dtVencto = DateTimeUtils::parseDateStr($parcelas[0]['dtVencto']);
            $dtVenctoEfetiva = DateTimeUtils::parseDateStr($parcelas[0]['dtVenctoEfetiva']);
            $valor = DecimalUtils::parseStr($parcelas[0]['valor']);
            $documentoNum = $parcelas[0]['documentoNum'] ?? null;
            $chequeNumCheque = $parcelas[0]['chequeNumCheque'] ?? null;
            $movimentacao->dtVencto = $dtVencto;
            $movimentacao->dtVenctoEfetiva = $dtVenctoEfetiva;
            $movimentacao->valor = $valor;
            $movimentacao->documentoNum = $documentoNum;
            $movimentacao->chequeNumCheque = $chequeNumCheque;
        } else {
            $movimentacao->dtVencto = clone $dtPrimeiroVencto;
            $proxDiaUtilFinanceiro = $repoDiaUtil->findDiaUtil($movimentacao->dtVencto, null, true);
            $movimentacao->dtVenctoEfetiva = $proxDiaUtilFinanceiro;
            $movimentacao->valor = $valorParcela;
        }


        $movimentacao->cadeiaQtde = $qtdeParcelas;
        $movimentacao->cadeiaOrdem = 1;
        $movimentacao->descricao = (strtoupper($movimentacao->descricao));

        $movimentacao->calcValorTotal();
        $cadeia->movimentacoes->add($movimentacao);

        $proxDtVencto = clone $dtPrimeiroVencto;
        for ($i = 2; $i <= $qtdeParcelas; $i++) {
            $parcela = clone $movimentacao;
            $parcela->cadeiaOrdem = $i;

            // Se foi passado o array com alterações nas parcelas
            if (isset($parcelas[$i - 1])) {
                $dtVencto = DateTimeUtils::parseDateStr($parcelas[$i - 1]['dtVencto']);
                $dtVenctoEfetiva = DateTimeUtils::parseDateStr($parcelas[$i - 1]['dtVenctoEfetiva']);
                $valor = DecimalUtils::parseStr($parcelas[$i - 1]['valor']);
                $documentoNum = $parcelas[$i - 1]['documentoNum'] ?? null;
                $chequeNumCheque = $parcelas[$i - 1]['chequeNumCheque'] ?? null;
                $parcela->dtVencto = $dtVencto;
                $parcela->dtVenctoEfetiva = $dtVenctoEfetiva;
                $parcela->valor = $valor;
                $parcela->documentoNum = $documentoNum;
                $parcela->chequeNumCheque = $chequeNumCheque;
            } else {
                $proxDtVencto = DateTimeUtils::incMes(clone $proxDtVencto);
                $parcela->dtVencto = $proxDtVencto;
                $proxDiaUtilFinanceiro = $repoDiaUtil->findDiaUtil($parcela->dtVencto, null, true);
                $parcela->dtVenctoEfetiva = $proxDiaUtilFinanceiro;
                if ($i === $qtdeParcelas) {
                    $parcela->valor = (bcadd($valorParcela, $resto, 2));
                }
            }
            $parcela->calcValorTotal();
            $cadeia->movimentacoes->add($parcela);
        }
    }

    /**
     * Salva um parcelamento.
     *
     * @param Movimentacao $primeiraParcela
     * @param $parcelas
     * @return Cadeia
     * @throws \Exception
     */
    public function salvarParcelas(Movimentacao $primeiraParcela, $parcelas): Cadeia
    {
        $this->doctrine->beginTransaction();

        $cadeiaParcelamento = new Cadeia();
        $this->doctrine->persist($cadeiaParcelamento);


        $i = 1;
        $valorTotal = 0.0;
        foreach ($parcelas as $parcela) {
            $movimentacao = clone $primeiraParcela;

            $movimentacao->cadeia = $cadeiaParcelamento;
            $movimentacao->cadeiaOrdem = $i++;

            $valor = (new NumberFormatter('pt_BR', NumberFormatter::DECIMAL))->parse($parcela['valor']);
            $movimentacao->valor = $valor;
            $valorTotal = bcadd($valor, $valorTotal);

            $dtVencto = \DateTime::createFromFormat('d/m/Y', $parcela['dtVencto']);
            $movimentacao->dtVencto = $dtVencto;

            $dtVenctoEfetiva = \DateTime::createFromFormat('d/m/Y', $parcela['dtVenctoEfetiva']);
            $movimentacao->dtVenctoEfetiva = $dtVenctoEfetiva;

            $documentoNum = $parcela['documentoNum'];
            $movimentacao->documentoNum = $documentoNum;

            // Em casos de grupos de itens...
            /** @var GrupoItem $giAtual */
            $giAtual = $movimentacao->grupoItem;
            if ($giAtual) {
                if ($giAtual->proximo !== null) {
                    $proximoId = $giAtual->proximo->getId();
                    $giAtual = $this->doctrine->getRepository(Grupo::class)->find($proximoId);
                } else {
                    $giAtual = $this->grupoBusiness->gerarNovo($giAtual->pai);
                }
                $movimentacao->grupoItem = $giAtual;
            }

            try {
                $this->movimentacaoEntityHandler->save($movimentacao);
            } catch (\Exception $e) {
                $msg = ExceptionUtils::treatException($e);
                $this->doctrine->rollback();
                throw new ViewException('Erro ao salvar parcelas (' . $msg . ')', 0);
            }
        }

        $this->doctrine->flush();

        $this->doctrine->commit();

        return $cadeiaParcelamento;


    }

    /**
     * Corrige os valores de OperadoraCartao.
     *
     * @param \DateTime $dtPagto
     * @param Carteira $carteira
     * @return array|string
     * @throws \Exception
     */
    public function corrigirOperadoraCartaoMovimentacoesCartoesDebito(\DateTime $dtPagto, Carteira $carteira)
    {

        $modo = $this->doctrine->getRepository(Modo::class)->findBy(['codigo' => 10]);

        $c101 = $this->doctrine->getRepository(Categoria::class)->findBy(['codigo' => 101]);
        $c102 = $this->doctrine->getRepository(Categoria::class)->findBy(['codigo' => 102]);
        $c299 = $this->doctrine->getRepository(Categoria::class)->findBy(['codigo' => 299]);
        $c199 = $this->doctrine->getRepository(Categoria::class)->findBy(['codigo' => 199]);

        /** @var MovimentacaoRepository $repo */
        $repo = $this->doctrine->getRepository(Movimentacao::class);

        $movs = $repo->findByFiltersSimpl(
            [
                ['carteira', 'EQ', $carteira],
                ['dtPagto', 'EQ', $dtPagto->format('Y-m-d')],
                ['modo', 'EQ', $modo],
                ['categoria', 'IN', [$c101, $c102]]
            ], null, 0, -1);

        $results = [];

        /** @var Movimentacao $mov */
        foreach ($movs as $mov) {

            $cadeia = $mov->cadeia;

            if (!$cadeia) {
                throw new \Exception('Movimentação sem $cadeia.');
            }
            // else

            try {

                /** @var Movimentacao $m299 */
                $m299 = $this->doctrine->getRepository(Movimentacao::class)->findOneBy(['$cadeia' => $cadeia,
                    'categoria' => $c299
                ]);

                /** @var Movimentacao $m199 */
                $m199 = $this->doctrine->getRepository(Movimentacao::class)->findOneBy(['$cadeia' => $cadeia,
                    'categoria' => $c199
                ]);

                $operadoraCartao = null;

                if ($m199->operadoraCartao === null) {
                    /** @var OperadoraCartao $operadoraCartao */
                    $operadoraCartao = $this->doctrine->getRepository(OperadoraCartao::class)->findOneBy(['carteira' => $m199->carteira]);
                    $m199->operadoraCartao = $operadoraCartao;

                    /** @var Movimentacao $m199 */
                    $m199 = $this->movimentacaoEntityHandler->save($m199);
                    $results[] = 'Operadora corrigida para "' . $m199->descricao . '" - R$ ' . $m199->valor . ' (1.99): ' . $operadoraCartao->descricao;
                } else {
                    $operadoraCartao = $m199->operadoraCartao;
                }

                if ($m299->operadoraCartao === null) {
                    // provavelmente TAMBÉM isso não deveria ser necessário, visto que na importação isto já deve ter sido acertado.
                    $m299->operadoraCartao = $operadoraCartao;
                    $m299 = $this->movimentacaoEntityHandler->save($m299);
                    $results[] = 'Operadora corrigida para "' . $m299->descricao . '" - R$ ' . $m299->valor . ' (2.99): ' . $operadoraCartao->descricao;
                }

                if ($mov->operadoraCartao === null) {
                    // provavelmente isso não deveria ser necessário, visto que na importação isto já deve ter sido acertado.
                    $mov->operadoraCartao = $operadoraCartao;
                    $mov = $this->movimentacaoEntityHandler->save($mov);
                    $results[] = 'Operadora corrigida para "' . $mov->descricao . '" - R$ ' . $mov->valor . ' (1.01): ' . $operadoraCartao->descricao;
                }

            } catch (\Exception $e) {
                $results[] = 'ERRO: Não foi possível consolidar ' . $mov->descricao . ' - R$ ' . $mov->valor . ' (' . $e->getMessage() . ')';
            }
        }

        return $results;
    }

    /**
     * Consolida as movimentações 101 lançadas manualmente com as 199/299 importadas pelo extrato.
     *
     * @param \DateTime $dtPagto
     * @param Carteira $carteira
     * @return array
     * @throws ViewException
     */
    public function consolidarMovimentacoesCartoesDebito(\DateTime $dtPagto, Carteira $carteira): array
    {
        $dtPagto->setTime(0, 0, 0, 0);
        $modo = $this->doctrine->getRepository(Modo::class)->find(10); // RECEB. CARTÃO DÉBITO
        $c101 = $this->doctrine->getRepository(Categoria::class)->findBy(['codigo' => 101]);
        $c102 = $this->doctrine->getRepository(Categoria::class)->findBy(['codigo' => 102]);

        /** @var MovimentacaoRepository $repo */
        $repo = $this->doctrine->getRepository(Movimentacao::class);

        $movs = $repo->findByFiltersSimpl(
            [
                ['carteira', 'EQ', $carteira],
                ['dtPagto', 'EQ', $dtPagto->format('Y-m-d')],
                ['modo', 'EQ', $modo],
                ['categoria', 'IN', [$c101, $c102]]
            ], null, 0, -1);

        $results = [];

        /** @var Movimentacao $mov */
        $this->movimentacaoEntityHandler->getDoctrine()->beginTransaction();
        foreach ($movs as $mov) {
            try {
                if ($mov->cadeia === null) {
                    $results[] = $this->consolidarMovimentacaoDebito($mov, $dtPagto, $carteira);
                }
            } catch (\Exception $e) {
                $results[] = 'ERRO: não foi possível consolidar ' . $mov->descricao . ' - R$ ' . $mov->valor . ' (' . $e->getMessage() . ')';
            }
        }
        $this->movimentacaoEntityHandler->getDoctrine()->commit();

        return $results;
    }

    /**
     * Faz a consolidação das movimentações de cartão de débito (após as correspondentes terem sido importadas).
     *
     * @param Movimentacao $m101
     * @param \DateTime $dtMoviment
     * @param Carteira $carteira
     * @return string
     * @throws ViewException
     */
    public function consolidarMovimentacaoDebito(Movimentacao $m101, \DateTime $dtMoviment, Carteira $carteira): string
    {

        /** @var Categoria $c299 */
        $c299 = $this->doctrine->getRepository(Categoria::class)->findBy(['codigo' => 299]);

        // pesquisa movimentação 299 nesta
        // retorna uma lista pois pode encontrar mais de 1

        $m299s = $this->doctrine->getRepository(Movimentacao::class)->findBy([
            'dtMoviment' => $dtMoviment,
            'valorTotal' => $m101->valor,
            'carteira' => $carteira,
            'bandeiraCartao' => $m101->bandeiraCartao,
            'categoria' => $c299
        ]);

        // Encontra a m299 que faça parte de uma $cadeia com apenas 2 movimentações: 199 e 299 (para evitar de incluir 2 vezes uma 101 na mesma $cadeia).
        $m299 = null;
        /** @var Movimentacao $_m299 */
        foreach ($m299s as $_m299) {
            if ($_m299->cadeia->movimentacoes->count() === 2) {
                $m299 = $_m299;
                break;
            }
        }

        if ($m299 === null) {
            $result = 'ERRO: Nenhuma movimentação 2.99 encontrada para "' . $m101->descricao . '" - R$ ' . number_format($m101->valor, 2, ',', '.');
            return $result;
        }

        // Incluir na $cadeia
        $m101->cadeia = $m299->cadeia;
        $m101->cadeiaOrdem = 3;
        $m299->cadeia->movimentacoes->add($m101);

        $this->movimentacaoEntityHandler->save($m101);
        // ...para poder atualizar a m299 no entityManager, e dessa forma saber que ela já está em uma $cadeia com 3 movimentações, pulando o if no for acima.

        $result = 'SUCESSO: Movimentação consolidada >> "' . $m101->descricao . '" - R$ ' . number_format($m101->valor, 2, ',', '.');

        return $result;
    }

    /**
     * Cálcula a taxa do cartão com base no valor lançado do custo financeiro mensal.
     * @param Carteira $carteira
     * @param $debito
     * @param $totalVendas
     * @param \DateTime $dtIni
     * @param \DateTime $dtFim
     * @return float
     */
    public function calcularTaxaCartao(Carteira $carteira, $debito, $totalVendas, \DateTime $dtIni, \DateTime $dtFim): float
    {
        if ($debito) {
            $cCustoOperacionalCartao = $this->doctrine->getRepository(Categoria::class)->findOneBy(['codigo' => 202005002]);
        } else {
            $cCustoOperacionalCartao = $this->doctrine->getRepository(Categoria::class)->findOneBy(['codigo' => 202005001]);
        }
        $tCustoOperacionalCartao = $this->doctrine->getRepository(Movimentacao::class)->findTotal($dtIni, $dtFim, $carteira, $cCustoOperacionalCartao);

        $taxaCartao = 0.0;

        if (($tCustoOperacionalCartao !== null) && ($totalVendas !== null) && ($tCustoOperacionalCartao > 0)
            && ($totalVendas > 0)) {
            $taxaCartao = bcmul(bcdiv($tCustoOperacionalCartao, $totalVendas, 6), 100, 2);
        }
        return $taxaCartao;
    }

    /**
     * Verifica se pode exibir os campos para setar/alterar a recorrência da movimentação.
     * Regras: somente se...
     *  - É um registro novo.
     *  - Ainda não for recorrente.
     *  - É recorrente, mas é a última da $cadeia.
     * @param Movimentacao $movimentacao
     * @return bool
     */
    public function exibirRecorrente(?Movimentacao $movimentacao): bool
    {
        if (!$movimentacao || !$movimentacao->getId() || $movimentacao->recorrente === false) {
            return true;
        }

        $cadeia = $movimentacao->cadeia;
        return !$cadeia || $cadeia->movimentacoes->last()->getId() === $movimentacao->getId();
    }

    /**
     * Processa um conjunto de movimentações e gera suas recorrentes.
     *
     * @param $movs
     * @return string
     */
    public function processarRecorrentes($movs): ?string
    {
        $this->doctrine->beginTransaction();
        try {
            $results = '';
            $i = 1;
            foreach ($movs as $mov) {
                $results .= $i++ . ' - ' . $this->processarRecorrente($mov) . "\r\n";
            }
            $this->doctrine->commit();
            return $results;
        } catch (\Exception $e) {
            $this->doctrine->rollback();
            throw new \RuntimeException('Erro ao processar recorrentes', 0, $e);
        }
    }

    /**
     * @param Movimentacao $originante
     * @return mixed
     * @throws \Exception
     */
    private function processarRecorrente(Movimentacao $originante)
    {
        $result = '';

        if (!$originante->recorrente) {
            // Tem que ter sido passada uma List com movimentações que sejam recorrentes
            throw new ViewException('Movimentação não recorrente não pode ser processada (' . $originante->descricao . ')');
        }

        if (!$originante->recorrFrequencia || $originante->recorrFrequencia === 'NENHUMA') {
            throw new ViewException('Recorrência com frequência = "NENHUMA" (' . $originante->descricao . ')');
        }
        if (!$originante->recorrTipoRepet || $originante->recorrTipoRepet === 'NENHUMA') {
            throw new ViewException('Recorrência com tipo de repetição = "NENHUMA" (' . $originante->descricao . ')');
        }

        // verifico se já existe a movimentação $posterior
        if ($originante->cadeia !== null) {

            $proxMes = (clone $originante->dtVencto)->add(new \DateInterval('P1M'));
            $dtIni = DateTimeUtils::getPrimeiroDiaMes($proxMes);
            $dtFim = DateTimeUtils::getUltimoDiaMes($proxMes);

            /** @var Movimentacao $posterior */
            $aPosterior = $this->doctrine->getRepository(Movimentacao::class)
                ->findByFiltersSimpl(
                    [['cadeia', 'EQ', $originante->cadeia],
                        ['dtVencto', 'BETWEEN', [$dtIni, $dtFim]]]);
            $posterior = $aPosterior[0] ?? null;


            // Só altera uma posterior caso não tenha dtPagto
            if ($posterior) {

                $posterior->recorrente = true;
                $posterior->recorrDia = $originante->recorrDia;
                $posterior->recorrVariacao = $originante->recorrVariacao;
                $posterior->recorrFrequencia = $originante->recorrFrequencia;
                $posterior->recorrTipoRepet = $originante->recorrTipoRepet;

                if ($posterior->dtPagto) {
                    $result = 'Posterior já realizada. Não será possível alterar: ' . $originante->descricao . '"';
                } // verifico se teve alterações na originante
                else if ($originante->getUpdated()->getTimestamp() > $posterior->getUpdated()->getTimestamp()) {

                    $posterior->descricao = $originante->descricao;

                    $posterior->valor = $originante->valor;
                    $posterior->acrescimos = $originante->acrescimos;
                    $posterior->descontos = $originante->descontos;
                    $posterior->valorTotal = null; // null para recalcular no beforeSave

                    $posterior->sacado = $originante->sacado;
                    $posterior->cedente = $originante->cedente;

                    $posterior->carteira = $originante->carteira;
                    $posterior->categoria = $originante->categoria;
                    $posterior->centroCusto = $originante->centroCusto;

                    $posterior->modo = $originante->modo;

                    $this->calcularNovaDtVencto($originante, $posterior);
                }
                try {
                    $this->movimentacaoEntityHandler->save($posterior);
                    $result = 'SUCESSO ao atualizar movimentação: ' . $originante->descricao;
                } catch (\Exception $e) {
                    $result = 'ERRO ao atualizar movimentação: ' . $originante->descricao . '. (' . $e->getMessage() . ')';
                }

                return $result;
            }
        }

        $salvarOriginal = false;

        $nova = clone $originante;
        $nova->UUID = null;

        $nova->setId(null);
        $nova->dtPagto = null;

        $cadeia = $originante->cadeia;

        // Se ainda não possui uma $cadeia...
        if ($cadeia !== null) {
            $nova->cadeiaOrdem = $cadeia->movimentacoes->count() + 1;
        } else {
            $cadeia = new Cadeia();

            // Como está sendo gerada uma $cadeia nova, tenho que atualizar a movimentação ||iginal e mandar salva-la também.
            $originante->cadeiaOrdem = 1;
            $originante->cadeia = $cadeia;
            $salvarOriginal = true; // tem que salvar a ||iginante porque ela foi incluída na $cadeia

            $nova->cadeiaOrdem = 2;
        }

        $cadeia->fechada = false;

        $nova->cadeia = $cadeia;

        $this->calcularNovaDtVencto($originante, $nova);

        $nova->status = 'ABERTA'; // posso setar como ABERTA pois no beforeSave(), se for CHEQUE, ele altera para A_COMPENSAR.

        /** @var TipoLancto $aPagarReceber */
        $aPagarReceber = $this->doctrine->getRepository(TipoLancto::class)->findOneBy(['codigo' => 20]);

        $nova->tipoLancto = $aPagarReceber;

        // seto o número do cheque para ????, para que seja informado $posteriormente.
        if ($nova->chequeNumCheque !== null) {
            $nova->chequeNumCheque = '????';
        }

        // Tem que salvar a $cadeia, pois foi removido os Cascades devido a outros problemas...

        $this->cadeiaEntityHandler->save($cadeia);

        if ($salvarOriginal) {
            try {
                $this->movimentacaoEntityHandler->save($originante);
                $result .= 'SUCESSO ao salvar movimentação originante: ' . $originante->descricao;
            } catch (\Exception $e) {
                $result .= 'ERRO ao salvar movimentação originante: ' . $originante->descricao . '. (' . $e->getMessage() . ')';
            }
            $nova->cadeia = $originante->cadeia;
        }

        try {
            $this->movimentacaoEntityHandler->save($nova);
            $result .= 'SUCESSO ao gerar movimentação: ' . $nova->descricao;
        } catch (\Exception $e) {
            $result .= 'ERRO ao atualizar movimentação: ' . $originante->descricao . '. (' . $e->getMessage() . ')';
        }

        return $result;
    }

    /**
     * @param Movimentacao $originante
     * @param Movimentacao $nova
     * @throws ViewException
     */
    private function calcularNovaDtVencto(Movimentacao $originante, Movimentacao $nova): void
    {
        /** @var DiaUtilRepository $repoDiaUtil */
        $repoDiaUtil = $this->doctrine->getRepository(DiaUtil::class);

        $novaDtVencto = clone $originante->dtVencto;
        if ($nova->recorrFrequencia === 'ANUAL') {
            $novaDtVencto = $novaDtVencto->setDate((int)$novaDtVencto->format('Y') + 1, $novaDtVencto->format('m'), $novaDtVencto->format('d'));
        } else {
            // uso o dia 1 aqui, pois ali embaixo ele vai acertar o dia conforme as outras regras
            $novaDtVencto = $novaDtVencto->setDate($novaDtVencto->format('Y'), (int)$novaDtVencto->format('m') + 1, 1);
        }


        if ($nova->recorrTipoRepet === 'DIA_FIXO') {
            // se foi marcado com dia da recorrência maior ou igual a 31
            // ou se estiver processando fevereiro e a data de vencimento for maior ou igual a 29...
            // então sempre setará para o último dia do mês
            if (($nova->recorrDia >= 31) || ($nova->recorrDia >= 29 && $novaDtVencto->format('m') === 2)) {
                // como já tinha adicionado +1 mês ali em cima, só pega o último dia do mês
                $novaDtVencto = \DateTime::createFromFormat('Y-m-d', $novaDtVencto->format('Y-m-t'));
            } else {
                $novaDtVencto->setDate($novaDtVencto->format('Y'), $novaDtVencto->format('m'), $nova->recorrDia);
            }
            $nova->dtVencto = clone $novaDtVencto;
        } else if ($nova->recorrTipoRepet === 'DIA_UTIL') {
            // Procuro o dia útil ordinalmente...
            $novaDtVencto = $novaDtVencto->setDate($novaDtVencto->format('Y'), (int)$novaDtVencto->format('m'), 01);

            $diaUtil = $repoDiaUtil->findEnesimoDiaUtil($novaDtVencto, $nova->recorrDia, true);
            $nova->dtVencto = $diaUtil;
        }

        $nova->dtVenctoEfetiva = null;
    }

    /**
     * Verifica se está pedindo para editar uma 1.99. Neste caso, troca para a 2.99.
     * @param Movimentacao $movimentacao
     * @return null
     * @throws \Exception
     */
    public function checkEditTransfPropria(Movimentacao $movimentacao)
    {
        if ($movimentacao->categoria && $movimentacao->categoria->codigo === 199) {

            $categ299 = $this->doctrine->getRepository(Categoria::class)->findOneBy(['codigo' => 299]);
            $cadeia = $movimentacao->cadeia;
            if ($cadeia === null) {
                throw new ViewException('Movimentação de transferência própria sem cadeia');
            }
            $moviment299 = $this->doctrine->getRepository(Movimentacao::class)->findOneBy(['cadeia' => $cadeia, 'categoria' => $categ299]);
            if (!$moviment299) {
                throw new ViewException('Cadeia de transferência própria já existe, porém sem a 2.99');
            }
            return $moviment299;
        }
        return null;
    }

    /**
     * @param \DateTime $data
     * @param Carteira $carteira
     * @return array
     */
    public function calcularSaldos(\DateTime $data, Carteira $carteira): array
    {

        try {
            $saldos = [];
            /** @var MovimentacaoRepository $movimentacaoRepo */
            $movimentacaoRepo = $this->doctrine->getRepository(Movimentacao::class);
            $saldoPosterior = $movimentacaoRepo->findSaldo($data, $carteira->getId(), 'SALDO_POSTERIOR_REALIZADAS');
            $saldoPosteriorComCheques = $movimentacaoRepo->findSaldo($data, $carteira->getId(), 'SALDO_POSTERIOR_COM_CHEQUES');
            $saldos['SALDO_POSTERIOR_REALIZADAS'] = $saldoPosterior;
            $saldos['SALDO_POSTERIOR_COM_CHEQUES'] = $saldoPosteriorComCheques;
            $saldos['TOTAL_CHEQUES'] = $saldoPosterior - $saldoPosteriorComCheques;
            return $saldos;
        } catch (\Exception $e) {
            throw new ViewException('Erro ao calcular saldos', 0, $e);
        }
    }

    /**
     * Altera as movimentações do lote com o que tiver sido setado em $movComAlteracoes
     *
     * @param array $lote
     * @param Movimentacao $movComAlteracoes
     * @throws ViewException
     */
    public function alterarEmLote(array $lote, Movimentacao $movComAlteracoes): void
    {
        /** @var Movimentacao $mov */
        foreach ($lote as $mov) {

            $this->movimentacaoEntityHandler->refindAll($movComAlteracoes);

            if ($movComAlteracoes->modo) {
                $mov->modo = $movComAlteracoes->modo;
            }

            if ($movComAlteracoes->documentoBanco) {
                $mov->documentoBanco = $movComAlteracoes->documentoBanco;
            }

            if ($movComAlteracoes->documentoNum) {
                $mov->documentoNum = $movComAlteracoes->documentoNum;
            }

            if ($movComAlteracoes->sacado) {
                $mov->sacado = $movComAlteracoes->sacado;
            }

            if ($movComAlteracoes->cedente) {
                $mov->cedente = $movComAlteracoes->cedente;
            }

            if ($movComAlteracoes->quitado) {
                $mov->quitado = $movComAlteracoes->quitado;
            }

            if ($movComAlteracoes->tipoLancto) {
                $mov->tipoLancto = $movComAlteracoes->tipoLancto;
            }

            if ($movComAlteracoes->carteira) {
                $mov->carteira = $movComAlteracoes->carteira;
            }

            if ($movComAlteracoes->carteiraDestino) {
                $mov->carteiraDestino = $movComAlteracoes->carteiraDestino;
            }

            if ($movComAlteracoes->categoria) {
                $mov->categoria = $movComAlteracoes->categoria;
            }

            if ($movComAlteracoes->centroCusto) {
                $mov->centroCusto = $movComAlteracoes->centroCusto;
            }

            
            
            

            if ($movComAlteracoes->grupoItem) {
                $mov->grupoItem = $movComAlteracoes->grupoItem;
            }

            if ($movComAlteracoes->status) {
                $mov->status = $movComAlteracoes->status;
            }

            if ($movComAlteracoes->descricao) {
                $mov->descricao = $movComAlteracoes->descricao;
            }

            if ($movComAlteracoes->dtMoviment) {
                $mov->dtMoviment = $movComAlteracoes->dtMoviment;
            }

            if ($movComAlteracoes->dtVencto) {
                $mov->dtVencto = $movComAlteracoes->dtVencto;
            }

            if ($movComAlteracoes->dtVenctoEfetiva) {
                $mov->dtVenctoEfetiva = $movComAlteracoes->dtVenctoEfetiva;
            }

            if ($movComAlteracoes->dtPagto) {
                $mov->dtPagto = $movComAlteracoes->dtPagto;
            }

            if ($movComAlteracoes->chequeBanco) {
                $mov->chequeBanco = $movComAlteracoes->chequeBanco;
            }

            if ($movComAlteracoes->chequeAgencia) {
                $mov->chequeAgencia = $movComAlteracoes->chequeAgencia;
            }

            if ($movComAlteracoes->chequeConta) {
                $mov->chequeConta = $movComAlteracoes->chequeConta;
            }

            if ($movComAlteracoes->chequeNumCheque) {
                $mov->chequeNumCheque = $movComAlteracoes->chequeNumCheque;
            }

            if ($movComAlteracoes->operadoraCartao) {
                $mov->operadoraCartao = $movComAlteracoes->operadoraCartao;
            }

            if ($movComAlteracoes->bandeiraCartao) {
                $mov->bandeiraCartao = $movComAlteracoes->bandeiraCartao;
            }

            if ($movComAlteracoes->recorrente) {
                $mov->recorrente = $movComAlteracoes->recorrente;
            }

            if ($movComAlteracoes->recorrDia) {
                $mov->recorrDia = $movComAlteracoes->recorrDia;
            }

            if ($movComAlteracoes->recorrFrequencia) {
                $mov->recorrFrequencia = $movComAlteracoes->recorrFrequencia;
            }

            if ($movComAlteracoes->recorrTipoRepet) {
                $mov->recorrTipoRepet = $movComAlteracoes->recorrTipoRepet;
            }

            if ($movComAlteracoes->recorrVariacao) {
                $mov->recorrVariacao = $movComAlteracoes->recorrVariacao;
            }

            if ($movComAlteracoes->valor) {
                $mov->valor = $movComAlteracoes->valor;
            }

            if ($movComAlteracoes->descontos) {
                $mov->descontos = $movComAlteracoes->descontos;
            }

            if ($movComAlteracoes->acrescimos) {
                $mov->acrescimos = $movComAlteracoes->acrescimos;
            }

            if ($movComAlteracoes->valorTotal) {
                $mov->valorTotal = $movComAlteracoes->valorTotal;
            }

            if ($movComAlteracoes->obs) {
                $mov->obs = $movComAlteracoes->obs;
            }
        }
    }

    /**
     * Monta os <option> para campo carteira.
     * FIXME: corrigir para o padrão do autoSelect2 do Crosier.
     *
     * @param $params
     * @return null|string
     * @throws ViewException
     */
    public function getFilterCarteiraOptions($params): ?string
    {
        /** @var CarteiraRepository $repoCarteira */
        $repoCarteira = $this->doctrine->getRepository(Carteira::class);
        $carteiras = $repoCarteira->findByFiltersSimpl([['atual', 'EQ', true]], ['e.codigo' => 'ASC'], 0, -1);

        $param = null;

        foreach ($params as $p) {
            if ($p->field[0] === 'e.carteira') {
                $param = $p;
                break;
            }
        }
        if (!$param) {
            return null;
        }

        $str = '';
        $selected = '';
        /** @var Carteira $carteira */
        foreach ($carteiras as $carteira) {
            if ($param->val) {
                if ($carteira->getId() === (int)$param->val) {
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }
            }
            $str .= '<option value="' . $carteira->getId() . '" ' . $selected . '>' . $carteira->getCodigo(true) . ' - ' . $carteira->descricao . '</option>';
        }
        return $str;
    }


    /**
     * "Paga" uma movimentação aberta com outra já realizada e em seguida exclui esta.
     * @param Movimentacao $aberta
     * @param Movimentacao $realizada
     */
    public function pagarAbertaComRealizada(Movimentacao $aberta, Movimentacao $realizada)
    {
        try {
            $this->movimentacaoEntityHandler->getDoctrine()->beginTransaction();
            $aberta->dtPagto = clone $realizada->dtPagto;
            $aberta->carteira = $realizada->carteira;
            $aberta->cedente = $aberta->cedente ?? $realizada->cedente;
            $aberta->sacado = $aberta->sacado ?? $realizada->sacado;

            $aberta->valor = $realizada->valor;
            $aberta->descontos = $realizada->descontos;
            $aberta->acrescimos = $realizada->acrescimos;
            $aberta->valorTotal = $realizada->valorTotal;

            $this->movimentacaoEntityHandler->save($aberta);
            $this->movimentacaoEntityHandler->delete($realizada);
            $this->movimentacaoEntityHandler->getDoctrine()->commit();
        } catch (\Exception $e) {
            $this->logger->error('Erro ao pagarAbertaComRealizada. aberta.id: ' . $aberta->getId() . '. realizada.id: ' . $realizada->getId());
            $this->movimentacaoEntityHandler->getDoctrine()->rollback();
        }
    }

    /**
     *
     * @throws ViewException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function corrigirDtVenctos(): void
    {
        $qry = $this->movimentacaoEntityHandler
            ->getDoctrine()
            ->createQuery("SELECT m FROM App\Entity\Financeiro\Movimentacao m WHERE m.dtPagto IS NULL AND (m.dtVencto != m.dtVenctoEfetiva OR m.dtUtil != m.dtVencto OR m.dtUtil != m.dtVenctoEfetiva)");
        $rs = $qry->getResult();

        /** @var DiaUtilRepository $repoDiaUtil */
        $repoDiaUtil = $this->doctrine->getRepository(DiaUtil::class);

        /** @var Movimentacao $mov */
        foreach ($rs as $mov) {
            $dtVenctoEfetivaCerta = $repoDiaUtil->findDiaUtil($mov->dtVencto, null, true);
            $dtVenctoEfetivaCerta->setTime(0, 0, 0, 0);
            $mov->dtVenctoEfetiva = clone $dtVenctoEfetivaCerta;
            $mov->dtUtil = $dtVenctoEfetivaCerta;
        }
        $this->movimentacaoEntityHandler->getDoctrine()->flush();
    }

    /**
     * @param Movimentacao $movimentacao
     * @return string
     */
    public function getEditingURL(Movimentacao $movimentacao): string
    {
        if (in_array($movimentacao->categoria->codigo, [199, 299])) {
            if ($movimentacao->cadeia->movimentacoes->count() === 2) {
                return '/fin/movimentacao/form/transferenciaEntreCarteiras/';
            }
            if ($movimentacao->cadeia->movimentacoes->count() === 3) {
                return '/fin/movimentacao/form/transferenciaEntradaCaixa/';
            }
        }
        if ($movimentacao->grupoItem) {
            return '/fin/movimentacao/form/grupo/';
        }
        if ($movimentacao->carteira->caixa) {
            return '/fin/movimentacao/form/caixa/';
        }
        if (!$movimentacao->dtPagto) {
            return '/fin/movimentacao/form/aPagarReceber/';
        }
        return '/fin/movimentacao/form/pagto/';
    }

    /**
     * @return array
     * @throws ViewException
     */
    public function getSelect2jsFiliais(): array
    {
        try {
            /** @var AppConfigRepository $repoAppConfig */
            $repoAppConfig = $this->doctrine->getRepository(AppConfig::class);
            $filiaisR = json_decode($repoAppConfig->findConfigByChaveAndAppNome('financeiro.filiais_prop.json', 'crosierapp-radx')->valor, true);
            if (!$filiaisR) {
                throw new \RuntimeException();
            }
            $filiais = [];
            foreach ($filiaisR as $documento => $nome) {
                $str = StringUtils::mascararCnpjCpf($documento) . ' - ' . $nome;
                $filiais[] = [
                    'id' => $str,
                    'text' => $str
                ];
            }
            return $filiais;
        } catch (\Exception $e) {
            throw new ViewException('financeiro.filiais_prop.json n/c');
        }
    }


}
