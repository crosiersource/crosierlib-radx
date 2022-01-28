<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Financeiro;


use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\BandeiraCartao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CentroCusto;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\ImportExtratoCabec;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\OperadoraCartao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\RegraImportacaoLinha;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\TipoLancto;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\BandeiraCartaoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CategoriaRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CentroCustoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\ModoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\MovimentacaoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\RegraImportacaoLinhaRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\TipoLanctoRepository;
use Doctrine\ORM\EntityManagerInterface;

const TXT_LINHA_NAO_IMPORTADA = '<<< LINHAS NÃO IMPORTADAS >>>';

const TXT_LINHA_IMPORTADA = '<<< LINHAS IMPORTADAS >>>';

/**
 * Classe responsável pelas regras de negócio de importação de extratos.
 *
 * @package CrosierSource\CrosierLibRadxBundle\Business\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class MovimentacaoImporter
{

    private EntityManagerInterface $doctrine;

    private $linhas;

    /**
     * Armazena linhas de descrição complementar para verificações durante o processo.
     */
    private array $linhasComplementares = array();

    /**
     * Armazena as movimentações de categoria 1.01 que já foram importadas.
     */
    private array $movs101JaImportadas = array();

    /**
     * Para armazenar as movimentações já importadas afim de que não sejam importadas 2x por duplicidade.
     */
    private array $movsJaImportadas = array();

    private $linhasExtrato;

    private $tipoExtrato;

    private $carteiraExtrato;

    private $carteiraDestino;

    private $grupoItem;

    private $gerarSemRegras;

    private $gerarAConferir;

    private $identificarPorCabecalho;

    private $arrayCabecalho;

    /** @var ModoRepository */
    private $repoModo;

    /** @var MovimentacaoRepository */
    private $repoMovimentacao;

    /** @var CategoriaRepository */
    private $repoCategoria;

    /** @var TipoLanctoRepository */
    private $repoTipoLancto;

    /** @var BandeiraCartaoRepository */
    private $repoBandeiraCartao;

    /** @var CentroCustoRepository */
    private $repoCentroCusto;


    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->repoModo = $this->doctrine->getRepository(Modo::class);
        $this->repoMovimentacao = $this->doctrine->getRepository(Movimentacao::class);
        $this->repoCategoria = $this->doctrine->getRepository(Categoria::class);
        $this->repoTipoLancto = $this->doctrine->getRepository(TipoLancto::class);
        $this->repoBandeiraCartao = $this->doctrine->getRepository(BandeiraCartao::class);
        $this->repoCentroCusto = $this->doctrine->getRepository(CentroCusto::class);
    }

    /**
     * @param $tipoExtrato
     * @param $linhasExtrato
     * @param Carteira|null $carteiraExtrato
     * @param Carteira|null $carteiraDestino
     * @param GrupoItem|null $grupoItem
     * @param $gerarSemRegras
     * @param bool $identificarPorCabecalho
     *
     * @return mixed
     *
     * @throws ViewException
     */
    public function importar($tipoExtrato, $linhasExtrato, ?Carteira $carteiraExtrato, ?Carteira $carteiraDestino,
                             ?GrupoItem $grupoItem, $gerarSemRegras, $identificarPorCabecalho = false, $gerarAConferir = true)
    {
        $this->tipoExtrato = $tipoExtrato;
        $this->linhasExtrato = $linhasExtrato;
        $this->carteiraExtrato = $carteiraExtrato;
        $this->carteiraDestino = $carteiraDestino;
        $this->grupoItem = $grupoItem;
        $this->gerarSemRegras = $gerarSemRegras;
        $this->gerarAConferir = $gerarAConferir;
        $this->identificarPorCabecalho = $identificarPorCabecalho;

        if ($identificarPorCabecalho) {
            $this->buildArrayCabecalho();
        }

        if (strpos($tipoExtrato, 'DEBITO') !== FALSE) {
            if (!$carteiraDestino || !$carteiraExtrato) {
                throw new ViewException('Para extratos de cartões de débito, é necessário informar a carteira de ||igem e de destino.');
            }
        } elseif (strpos($tipoExtrato, 'GRUPO') !== FALSE) {
            if (!$grupoItem) {
                throw new ViewException('Para extratos de grupos de movimentações, é necessário informar o grupo.');
            }
        }

        switch ($tipoExtrato) {
            case 'EXTRATO_GRUPO_MOVIMENTACOES':
                return $this->importGrupoMovimentacao();
            default:
                return $this->importarPadrao();
        }
    }


    /**
     * Constrói o array 'de-para' baseado no cabeçalho.
     *
     * @throws ViewException
     */
    public function buildArrayCabecalho(): void
    {
        $linhas = explode("\n", $this->linhasExtrato);
        $primeira = $linhas[0];
        if (strpos($primeira, '<<< LINHAS NÃO IMPORTADAS >>>') !== FALSE) {
            $primeira = $linhas[1];
        }
        $camposCSV = explode("\t", $primeira);

        $arrayCabecalho = [];

        $camposDePara = $this->doctrine->getRepository(ImportExtratoCabec::class)->findBy(['tipoExtrato' => $this->tipoExtrato]);
        if ($camposDePara) {
            /** @var ImportExtratoCabec $dePara */
            foreach ($camposDePara as $dePara) {
                // Se não está separado por vírgula, é um campo único (1-para-1).
                if (strpos($dePara->camposCabecalho, ',') === FALSE) {
                    $achou = false;
                    foreach ($camposCSV as $key => $campoCSV) {
                        if ($dePara->camposCabecalho === $campoCSV) {
                            $arrayCabecalho[$dePara->campoSistema] = $key;
                            $achou = true;
                            break;
                        }
                    }
                    if (!$achou) {
                        throw new ViewException('Não foi possível montar o array do cabeçalho.');
                    }
                } else {
                    $camposCabecalho = explode(',', $dePara->camposCabecalho);
                    foreach ($camposCabecalho as $campoCabecalho) {
                        $achou = false;
                        foreach ($camposCSV as $key => $campoCSV) {
                            if ($campoCabecalho === $campoCSV) {
                                $arrayCabecalho[$dePara->campoSistema]['campos'][] = $key;
                                $achou = true;
                                break;
                            }
                        }
                        if (!$achou) {
                            throw new ViewException('Não foi possível montar o array do cabeçalho.');
                        }
                    }
                    $arrayCabecalho[$dePara->campoSistema]['formato'] = $dePara->formato;
                }
            }
        }

        $this->arrayCabecalho = $arrayCabecalho;

    }

    /**
     * @return mixed
     */
    private function importarPadrao()
    {
        $this->movsJaImportadas = [];


        $linhasNaoImportadas = array();
        $linhasImportadas = array();

        $this->linhas = explode("\n", $this->linhasExtrato);

        $r = [];
        $r['LINHAS_RESULT'] = null;
        $r['movs'] = null;
        $r['err'] = null;

        $qtdeLinhas = count($this->linhas);

        for ($i = 0; $i < $qtdeLinhas; $i++) {
            if ($this->identificarPorCabecalho && $this->arrayCabecalho && $i === 0) {
                // pula o cabeçalho
                continue;
            }
            $linha = trim($this->linhas[$i]);

            // Verifica se é uma linha (de descrição) complementar já importada
            if (in_array($i, $this->linhasComplementares, true)) {
                $linhasImportadas[] = $linha;
                continue;
            }

            if (!$linha || trim($linha) === TXT_LINHA_IMPORTADA || trim($linha) === TXT_LINHA_NAO_IMPORTADA) {
                continue;
            }

            if ($this->tipoExtrato === 'EXTRATO_SIMPLES' && !$this->ehLinhaExtratoSimplesOuSaldo($linha)) {
                $linhasNaoImportadas[] = $linha;
                continue;
            }

            try {
                // importa a linha
                $movimentacao = $this->importarLinha($i);
                if ($movimentacao) {
                    $this->movsJaImportadas[] = $movimentacao;
                    $linhasImportadas[] = $linha;
                } else {
                    $linhasNaoImportadas[] = $linha;
                }
            } catch (ViewException $e) {
                $r['err'][] = [
                    'linha' => $linha,
                    'errMsg' => $e->getMessage()
                ];
                $linhasNaoImportadas[] = $linha;
            } catch (\Throwable $e) {
                $r['err'][] = [
                    'linha' => $linha,
                    'errMsg' => 'Erro geral ao processar linha: ' . $linha
                ];
                $linhasNaoImportadas[] = $linha;
            }


        }

        $r['LINHAS_RESULT'] = '';
        if (count($linhasNaoImportadas) > 0) {
            $r['LINHAS_RESULT'] .= TXT_LINHA_NAO_IMPORTADA . "\n" .
                implode("\n", $linhasNaoImportadas) . "\n\n\n\n\n";
        }
        $r['LINHAS_RESULT'] .= TXT_LINHA_IMPORTADA . "\n" .
            implode("\n", $linhasImportadas);

        $r['movs'] = $this->movsJaImportadas;

        return $r;
    }

    /**
     * @param $numLinha
     * @return mixed
     * @throws ViewException
     */
    private function importarLinha($numLinha)
    {
        switch ($this->tipoExtrato) {
            case 'EXTRATO_SIMPLES':
                $camposLinha = $this->importLinhaExtratoSimples($numLinha);
                break;
            case 'EXTRATO_MODERNINHA_DEBITO':
                $camposLinha = $this->importLinhaExtratoModerninhaDebito($numLinha);
                break;
            case 'EXTRATO_CIELO_DEBITO':
                $camposLinha = $this->importLinhaExtratoCieloDebitoNovo($numLinha);
                break;
            case 'EXTRATO_CIELO_CREDITO':
                $camposLinha = $this->importLinhaExtratoCieloCreditoNovo($numLinha);
                break;
            case 'EXTRATO_STONE_DEBITO':
                $camposLinha = $this->importLinhaExtratoStoneDebito($numLinha);
                break;
            case 'EXTRATO_STONE_CREDITO':
                $camposLinha = $this->importLinhaExtratoStoneCredito($numLinha);
                break;
            default:
                throw new ViewException('Tipo de extrato inválido.');
        }

        if ($camposLinha) {
            if (strpos($this->tipoExtrato, 'DEBITO') !== FALSE) {
                return $this->handleLinhaImportadaDebito($camposLinha);
            }

            return strpos($this->tipoExtrato, 'CREDITO') !== FALSE ?
                $this->handleLinhaImportadaCredito($camposLinha) :
                $this->handleLinhaImportadaPadrao($camposLinha);
        }

        return null;
    }

    /**
     * @param $camposLinha
     * @return Carteira|Movimentacao|OperadoraCartao
     * @throws ViewException
     */
    private function handleLinhaImportadaDebito($camposLinha)
    {
        $descricao = $camposLinha['descricao'];
        /** @var \DateTime $dtMoviment */
        $dtMoviment = $camposLinha['dtMoviment'];
        $dtVenctoEfetiva = $camposLinha['dtVenctoEfetiva'];
        $valor = $camposLinha['valor'];
        $valorTotal = $camposLinha['valorTotal'];
        $bandeiraCartao = $camposLinha['bandeiraCartao'];


        /** @var Categoria $categ101 */
        $categ101 = $this->repoCategoria->findOneBy(['codigo' => 101]);
        /** @var Categoria $categ102 */
        $categ102 = $this->repoCategoria->findOneBy(['codigo' => 102]);
        /** @var Categoria $categ299 */
        $categ299 = $this->repoCategoria->findOneBy(['codigo' => 299]);

        /** @var TipoLancto $transfPropria */
        $transfPropria = $this->repoTipoLancto->findOneBy(['codigo' => 60]);


        /** @var Modo $modo */
        $modo = $this->repoModo->find(10); // 'RECEB. CARTÃO DÉBITO';

        // Primeiro tento encontrar a movimentação original do cartão, que é a movimentação de entrada (101) no caixa a vista (anotado na folhinha de fechamento de caixa, lançado manualmente).
        $dtMoviment = $dtMoviment->setTime(0, 0, 0, 0);
        $dtMovimentIni = clone $dtMoviment;
        $dtMovimentFim = (clone $dtMoviment)->add(new \DateInterval('P5D'));
        $movs101Todas = $this->repoMovimentacao
            ->findByFiltersSimpl([
                ['dtMoviment', 'BETWEEN_DATE', [$dtMovimentIni, $dtMovimentFim]],
                ['valorTotal', 'EQ', $valorTotal],
                ['carteira', 'EQ', $this->carteiraDestino],
                ['bandeiraCartao', 'EQ', $bandeiraCartao],
                ['categoria', 'IN', [$categ101, $categ102]]
            ],
                ['dtMoviment' => 'ASC'], 0, -1);


        // Ignora as que já foram importadas (ou melhor, associadas, pois pode ter uma mesma movimentação, com mesmo valor,
        // mesma data, mesma bandeira
        $mov101 = null;
        /** @var Movimentacao $_mov101 */
        foreach ($movs101Todas as $_mov101) {
            if (!in_array($_mov101->getId(), $this->movs101JaImportadas, true)) {
                $mov101 = $_mov101;
                break;
            }
        }
        if ($mov101) {
            $this->movs101JaImportadas[] = $mov101->getId();
        }

        // Se não encontrar, avisa
        if (!$mov101) {
            throw new ViewException('Movimentação (1.01) original não encontrada (' . $descricao . ' - R$ ' . number_format($valorTotal, 2, '.', ','));
        }

        // Aqui as carteiras são invertidas, pois é a 299 (a destino do método é a do extrato, e a destino da importação é a 'origem' no método)
        $movs299Todas = $this->repoMovimentacao
            ->findByFiltersSimpl([
                ['dtMoviment', 'BETWEEN_DATE', [$dtMovimentIni, $dtMovimentFim]],
                ['valorTotal', 'EQ', $valorTotal],
                ['carteira', 'EQ', $this->carteiraDestino],
                ['carteiraDestino', 'EQ', $this->carteiraExtrato],
                ['bandeiraCartao', 'EQ', $bandeiraCartao],
                ['categoria', 'EQ', $categ299]
            ],
                ['dtMoviment' => 'ASC'], 0, -1);


        // Remove as já importadas para resolver o bug de ter duas movimentações de mesma bandeira e mesmo valor no mesmo dia
        $jaTem101Associada = false;
        /** @var Movimentacao $mov299 */
        foreach ($movs299Todas as $mov299) {
            if ($mov299->cadeia) {
                foreach ($mov299->cadeia->movimentacoes as $movCadeia) {
                    if ($movCadeia->categoria->codigo === 101 && $movCadeia->getId() !== $mov101->getId()) {
                        $jaTem101Associada = true;
                        break;
                    }
                }
            }
            if (!$jaTem101Associada && !$this->checkJaImportada($mov299)) {
                return $mov299;
            }
        }


        // Crio as movimentações 299 (no caixa AV) e 199 (na carteira extrato)

        $mov299 = new Movimentacao();
        // aqui se inverte as carteiras, pois para salvar uma transferência entre carteiras se deve sempre começar pela 299 (ver como funciona o MovimentacaoDataMapperImpl.processSave)
        $mov299->carteira = $this->carteiraDestino; // vai debitar no 'CAIXA A VISTA'
        $mov299->carteiraDestino = ($this->carteiraExtrato); // vai creditar na carteira do cartão (199)
        $mov299->categoria = ($categ299);
        $mov299->valor = ($valor);
        $mov299->descontos = (0.00);
        $mov299->valorTotal = ($valorTotal);
        $mov299->descricao = ($descricao);
        $mov299->tipoLancto = ($transfPropria); // para gerar as duas (299+199)
        $mov299->status = ('REALIZADA');
        $mov299->modo = ($modo);
        $mov299->dtMoviment = ($mov101->dtMoviment);
        $mov299->dtVencto = ($dtVenctoEfetiva);
        $mov299->dtVenctoEfetiva = ($dtVenctoEfetiva); // por questão de informação, a data efetiva em que o cartão pagou o valor fica na dt vencto nossa
        // tenho que deixar a dtPagto como a dtMoviment porque a 299
        // no caixa a vista tem que ser com a mesma data da 101 (que foi lançada através do fechamento de caixa diário).
        // e não posso ter uma 199 com data diferente da 299 correspondente
        $mov299->dtPagto = $mov101->dtMoviment;

        $mov299->bandeiraCartao = $bandeiraCartao;
        $mov299->UUID = StringUtils::guidv4();

        /** @var OperadoraCartao $operadoraCartao */
        $operadoraCartao = $this->doctrine->getRepository(OperadoraCartao::class)->findOneBy(['carteira' => $this->carteiraExtrato]);

        $mov299->operadoraCartao = $operadoraCartao;

        return $mov299;
    }

    /**
     * @param $camposLinha
     * @return Movimentacao|null|object
     * @throws ViewException
     */
    private function handleLinhaImportadaCredito($camposLinha)
    {
        $valor = $camposLinha['valor'];
        $desconto = 0.00;
        $valorTotal = $camposLinha['valor'];
        $valorNegativo = $valor < 0.0;
        $valor = abs($valor);
        $categoriaCodigo = $camposLinha['categoriaCodigo'];
        $descricao = $camposLinha['descricao'];
        $dtMoviment = $camposLinha['dtMoviment'];
        $dtVenctoEfetiva = $camposLinha['dtVenctoEfetiva'];
        $modo = $camposLinha['modo'];
        $planoPagtoCartao = $camposLinha['planoPagtoCartao'];
        $bandeiraCartao = $camposLinha['bandeiraCartao'];
        $numCheque = null;


        $movs = $this->repoMovimentacao
            ->findBy([
                'descricao' => mb_strtoupper($descricao),
                'valor' => $valor,
                'bandeiraCartao' => $bandeiraCartao,
                'modo' => $modo,
                'dtVenctoEfetiva' => $dtVenctoEfetiva
            ]);

        // Se achou alguma movimentação já lançada, pega a primeira
        if ($movs) {

            if (count($movs) > 1) {
                throw new ViewException('Mais de uma movimentação encontrada para "' . $descricao . '"');
            }

            return $movs[0];
        }
        // else
        // se for pra gerar movimentações que não se encaixem nas regras...
        $movimentacao = new Movimentacao();
        $movimentacao->UUID = (StringUtils::guidv4());
        $movimentacao->carteira = ($this->carteiraExtrato);
        $movimentacao->valor = ($valor);
        $movimentacao->descontos = ($desconto);
        $movimentacao->valorTotal = ($valorTotal);
        $movimentacao->descricao = ($descricao);

        /** @var TipoLancto $realizada */
        $realizada = $this->repoTipoLancto->findOneBy(['codigo' => 20]);
        $movimentacao->tipoLancto = ($realizada);

        $movimentacao->status = ('REALIZADA');
        $movimentacao->modo = ($modo);
        $movimentacao->dtMoviment = ($dtMoviment);
        $movimentacao->dtVencto = ($dtVenctoEfetiva);
        $movimentacao->dtVenctoEfetiva = ($dtVenctoEfetiva);
        $movimentacao->dtPagto = ($dtVenctoEfetiva);
        $movimentacao->bandeiraCartao = ($bandeiraCartao);

        /** @var Categoria $categoria */
        $categoria = null;
        if ($categoriaCodigo) {
            $categoria = $this->repoCategoria->findOneBy(['codigo' => $categoriaCodigo]);
        } else {
            if ($valorNegativo) {
                $categoria = $this->repoCategoria->findOneBy(['codigo' => 2]);
            } else {
                $categoria = $this->repoCategoria->findOneBy(['codigo' => 1]);
            }
        }
        $movimentacao->categoria = $categoria;

        return $movimentacao;
    }

    /**
     * @param $camposLinha
     * @return Movimentacao|null|object
     * @throws ViewException
     */
    private function handleLinhaImportadaPadrao($camposLinha)
    {
        $valor = $camposLinha['valor'];
        $desconto = $camposLinha['desconto'] ?? null;
        $valorTotal = $camposLinha['valorTotal'];
        $categoriaCodigo = $camposLinha['categoriaCodigo'];
        $valorNegativo = $valor < 0.0;
        $valor = abs($valor);
        $descricao = trim($camposLinha['descricao']);

        /** @var Modo $modo */
        $modo = $camposLinha['modo'];

        if ($this->gerarAConferir) {
            if (!$categoriaCodigo) {
                $categoriaCodigo = $valorNegativo ? 295 : 195;
            }
            if (!$modo) {

                if (strpos($descricao, 'TRANSF') !== FALSE || strpos($descricao, 'TED') !== FALSE) {
                    $modo = $this->repoModo->findOneBy(['codigo' => 7]);
                } else if (strpos($descricao, 'DEPÓSITO') !== FALSE || strpos($descricao, 'DEPOSITO') !== FALSE) {
                    $modo = $this->repoModo->findOneBy(['codigo' => 5]);
                } else if (strpos($descricao, 'TÍTULO') !== FALSE || strpos($descricao, 'TITULO') !== FALSE) {
                    $modo = $this->repoModo->findOneBy(['codigo' => 6]);
                } else {
                    $modo = $this->repoModo->findOneBy(['codigo' => 99]);
                }
            }
        }


        $dtMoviment = $camposLinha['dtMoviment'];
        /** @var \DateTime $dtVenctoEfetiva */
        $dtVenctoEfetiva = $camposLinha['dtVenctoEfetiva'];
        // $entradaOuSaida = $camposLinha['entradaOuSaida'];

        $planoPagtoCartao = $camposLinha['planoPagtoCartao'];
        $bandeiraCartao = $camposLinha['bandeiraCartao'];
        $numCheque = null;


        /** @var RegraImportacaoLinhaRepository $repoRegraImportacaoLinha */
        $repoRegraImportacaoLinha = $this->doctrine->getRepository(RegraImportacaoLinha::class);
        $regras = $repoRegraImportacaoLinha->findAllBy($this->carteiraExtrato);

        /** @var RegraImportacaoLinha $regra */
        $regra = null;
        /** @var RegraImportacaoLinha $r */
        foreach ($regras as $r) {
            if ($r->regraRegexJava) {
                if (preg_match('@' . $r->regraRegexJava . '@', $descricao)) {
                    if ($r->sinalValor === 0 ||
                        ($r->sinalValor === -1 && $valorNegativo) ||
                        ($r->sinalValor === 1 && !$valorNegativo)) {
                        $regra = $r;
                        break;
                    }
                }
            }
        }

        if ($regra) {
            preg_match('@' . $regra->regraRegexJava . '@', $descricao, $matches);
            if (isset($matches['NUMCHEQUE'])) {
                $numCheque = (int)preg_replace('[^\\d]', '', $matches['NUMCHEQUE']);
            }
        }

        $movsAbertasDiasAnteriores = [];

        // Se é uma linha de cheque
        if ($numCheque) {
            if ($valorNegativo) {
                $modo = $valorNegativo ? $this->repoModo->findBy(['codigo' => 3]) : $this->repoModo->findBy(['codigo' => 4]); // CHEQUE PRÓPRIO
            }
            $filterByCheque = [
                ['carteira', 'EQ', $this->carteiraExtrato],
                ['chequeNumCheque', 'LIKE_ONLY', $numCheque]
            ];
            $movsAbertasMesmoDia = $this->repoMovimentacao->findByFiltersSimpl($filterByCheque, null, 0, -1);
        } else {

            // Primeiro tenta encontrar movimentações em aberto de qualquer carteira, com o mesmo valor e dtVencto
            // Depois tenta encontrar movimentações de qualquer status somente da carteira do extrato
            // Junto os dois resultados
            $filtersSimplAbertasMesmoDia = [
                ['dtVenctoEfetiva', 'EQ', $dtVenctoEfetiva->format('Y-m-d')],
                ['valor', 'EQ', $valor],
                ['status', 'EQ', 'ABERTA']
            ];
            $movsAbertasMesmoDia = $this->repoMovimentacao->findByFiltersSimpl($filtersSimplAbertasMesmoDia, null, 0, -1);

            // Depois de pesquisar nas movimentações abertas do mesmo dia, pesquisará dos últimos 5 dias.
            $umDiaAntes = (clone $dtVenctoEfetiva)->setDate($dtVenctoEfetiva->format('Y'), $dtVenctoEfetiva->format('m'), $dtVenctoEfetiva->format('d') - 1);  
            $seisDiasAntes = (clone $dtVenctoEfetiva)->setDate($dtVenctoEfetiva->format('Y'), $dtVenctoEfetiva->format('m'), $dtVenctoEfetiva->format('d') - 6);  
            $filtersSimplAbertasDiasAnteriores = [
                ['dtVenctoEfetiva', 'BETWEEN_DATE', [$seisDiasAntes->format('Y-m-d'),$umDiaAntes->format('Y-m-d')]],
                ['valor', 'EQ', $valor],
                ['status', 'EQ', 'ABERTA']
            ];
            $movsAbertasDiasAnteriores = $this->repoMovimentacao->findByFiltersSimpl($filtersSimplAbertasDiasAnteriores, ['dtVenctoEfetiva' => 'DESC'], 0, -1);
        }

        $filtersSimplTodas = [
            ['dtUtil', 'EQ', $dtVenctoEfetiva->format('Y-m-d')],
            ['valorTotal', 'EQ', $valor],
            ['carteira', 'EQ', $this->carteiraExtrato]
        ];

        $movsTodas = $this->repoMovimentacao->findByFiltersSimpl($filtersSimplTodas, null, 0, -1);

        // array para atribuir a união dos outros dois
        $movs = [];
        /** @var Movimentacao $mov */
        foreach ($movsAbertasMesmoDia as $mov) {
            if ((!$this->checkJaImportada($mov)) && !in_array($mov->getId(), $movs, true)) {
                $movs[] = $mov->getId();
            }
        }
        // array para atribuir a união dos outros dois
        /** @var Movimentacao $mov */
        foreach ($movsAbertasDiasAnteriores as $mov) {
            if ((!$this->checkJaImportada($mov)) && !in_array($mov->getId(), $movs, true)) {
                $movs[] = $mov->getId();
            }
        }
        /** @var Movimentacao $mov */
        foreach ($movsTodas as $mov) {
            if ((!$this->checkJaImportada($mov)) && !in_array($mov->getId(), $movs, true)) {
                $movs[] = $mov->getId();
            }
        }


        // Se achou alguma movimentação já lançada, pega a primeira
        if (count($movs) > 0) {
            /** @var Movimentacao $movimentacao */
            $movimentacao = $this->repoMovimentacao->find($movs[0]);
            if (!$movimentacao->UUID) {
                $movimentacao->UUID = StringUtils::guidv4();
            }
            $movimentacao->dtPagto = ($dtVenctoEfetiva);
            $movimentacao->status = ('REALIZADA');
            $movimentacao->carteira = ($this->carteiraExtrato);
            return $movimentacao;
        }
        // else
        if ($regra) {
            $movimentacao = new Movimentacao();

            $movimentacao->UUID = StringUtils::guidv4();

            $carteiraOrigem = $regra->carteira ? $regra->carteira : $this->carteiraExtrato;
            $carteiraDestino = $regra->carteiraDestino ? $regra->carteiraDestino : $this->carteiraDestino;

            $movimentacao->carteira = ($carteiraOrigem);
            $movimentacao->carteiraDestino = ($carteiraDestino);

            if ($regra->tipoLancto->codigo === 60) {
                // Nas transferências entre contas próprias, a regra informa a carteira de ||igem.
                // A de destino, se não for informada na regra, será a do extrato.

                if (!$regra->categoria->codigo === '299') {
                    throw new ViewException('Regras para transferências entre carteiras próprias devem ser apenas com categoria 2.99');
                }

                // Se a regra informar a carteira da 299, prevalesce
                $cart299 = $regra->carteira ?: $this->carteiraExtrato;

                $cart199 = $regra->carteiraDestino;
                if ((!$cart199) || $cart199->codigo === '99') {
                    $cart199 = $this->carteiraExtrato;
                }

                $movimentacao->carteira = ($cart299);
                $carteiraDestino = $cart199;
                $movimentacao->carteiraDestino = ($carteiraDestino);
                // se NÃO for regra para TRANSF_PROPRIA
            } else {
                if (in_array($regra->tipoLancto->codigo, [40, 41], true)) {

                    $movimentacao = $this->repoMovimentacao
                        ->findOneBy([
                            'valor' => $valorTotal,
                            'carteira' => $this->carteiraExtrato,
                            'chequeNumCheque' => $numCheque
                        ]);


                    if ($movimentacao && $this->checkJaImportada($movimentacao)) {
                        $movimentacao = null;
                    }

                    // Se achou a movimentação deste cheque, só seta a dtPagto
                    if ($movimentacao) {
                        $movimentacao->setDtPagto($dtVenctoEfetiva);
                        return $movimentacao;
                    }
                    // else
                    $movimentacao = new Movimentacao();
                    $movimentacao->UUID = (StringUtils::guidv4());
                    $movimentacao->chequeNumCheque = ($numCheque);
                    /** @var Carteira $carteira */
                    $carteira = $regra->carteira ?: $carteiraOrigem;
                    $movimentacao->carteira = ($carteira);
                    $movimentacao->chequeBanco = ($carteira->banco);
                    $movimentacao->chequeAgencia = ($carteira->agencia);
                    $movimentacao->chequeConta = ($carteira->conta);

                } else if (in_array($regra->tipoLancto->codigo, [42, 43], true)) {
                    $movimentacao->chequeNumCheque = $numCheque;

                    if ($regra->chequeConta) {
                        $movimentacao->chequeAgencia = ($regra->chequeAgencia);
                        $movimentacao->chequeConta = ($regra->chequeConta);
                        $movimentacao->chequeBanco = ($regra->chequeBanco);
                    } else {
                        $movimentacao->chequeAgencia = ('9999');
                        $movimentacao->chequeConta = ('99999-9');
                        $movimentacao->chequeBanco = (null);
                    }
                }
            }

            $movimentacao->tipoLancto = ($regra->tipoLancto);


            if ($movimentacao->tipoLancto->codigo === 60) {
                $movimentacao->carteiraDestino = ($carteiraDestino);
            }

            $movimentacao->descricao = ($descricao);

            $movimentacao->categoria = $regra->categoria;
            $movimentacao->centroCusto = ($regra->centroCusto);

            $movimentacao->dtMoviment = ($dtVenctoEfetiva);
            $movimentacao->dtVencto = ($dtVenctoEfetiva);

            $movimentacao->status = $regra->status;

            $movimentacao->modo = ($regra->modo);
            $movimentacao->valor = ($valor);
            $movimentacao->valorTotal = ($valor);

            if ($regra->status === 'REALIZADA') {
                $movimentacao->dtPagto = ($dtVenctoEfetiva);
            }

            return $movimentacao;
        }
        // else
        if ($this->gerarSemRegras) {
            // se for pra gerar movimentações que não se encaixem nas regras...
            $movimentacao = new Movimentacao();
            $movimentacao->UUID = (StringUtils::guidv4());
            $movimentacao->carteira = ($this->carteiraExtrato);
            $movimentacao->valor = ($valor);
            $movimentacao->descontos = ($desconto);
            $movimentacao->valorTotal = ($valorTotal);
            $movimentacao->descricao = ($descricao);
            /** @var TipoLancto $realizada */
            $realizada = $this->repoTipoLancto->findOneBy(['codigo' => 20]);
            $movimentacao->tipoLancto = ($realizada);
            $movimentacao->status = ('REALIZADA');
            $movimentacao->modo = ($modo);
            $movimentacao->dtMoviment = ($dtMoviment);
            $movimentacao->dtVencto = ($dtVenctoEfetiva);
            $movimentacao->dtVenctoEfetiva = ($dtVenctoEfetiva);
            $movimentacao->dtPagto = ($dtVenctoEfetiva);
            $movimentacao->bandeiraCartao = ($bandeiraCartao);


            /** @var Categoria $categoria */
            $categoria = null;
            if ($categoriaCodigo) {
                $categoria = $this->repoCategoria->findOneBy(['codigo' => $categoriaCodigo]);
            } else if ($valorNegativo) {
                $categoria = $this->repoCategoria->findOneBy(['codigo' => 2]);
            } else {
                $categoria = $this->repoCategoria->findOneBy(['codigo' => 1]);
            }
            $movimentacao->categoria = ($categoria);

            return $movimentacao;
        }

        return null;
    }


    /**
     * Verifica se é uma linha normal (DATA DESCRIÇÃO VALOR) ou não.
     * @param $linha
     * @return bool
     */
    private function ehLinhaExtratoSimplesOuSaldo($linha): bool
    {
        if (strpos(str_replace(' ', '', $linha), 'SALDO') !== FALSE) {
            return true;
        }
        if (preg_match(StringUtils::PATTERN_DATA, $linha, $matches) && preg_match(StringUtils::PATTERN_MONEY, $linha, $matches)) {
            return true;
        }

        return false;
    }


    /**
     * @param $numLinha
     * @return mixed
     * @throws ViewException
     */
    private function importLinhaExtratoSimples($numLinha)
    {
        $linha = trim($this->linhas[$numLinha]);

        $antesDoPrimeiroEspaco = substr($linha, 0, StringUtils::strposRegex($linha, '\s'));
        $provavelData = substr($antesDoPrimeiroEspaco, 0, 10);

        $dataStr = DateTimeUtils::parseDateStr($provavelData)->format('d/m/Y');
        $linha = substr($linha, StringUtils::strposRegex($linha, '\s') + 1);

        preg_match(StringUtils::PATTERN_MONEY, $linha, $matches);
        $matches['SINAL_F'] = isset($matches['SINAL_F']) && $matches['SINAL_F'] === 'D' ? '-' : ($matches['SINAL_F'] ?? null);
        $valorStr = ($matches['SINAL_I'] ?: $matches['SINAL_F'] ?: '') . $matches['money'];

        $dtVenctoEfetiva = DateTimeUtils::parseDateStr($dataStr);

        $valor = StringUtils::parseFloat($valorStr, true);

        $entradaOuSaida = $valor < 0 ? 2 : 1;

        $descricao = trim(str_replace($valorStr, '', $linha));
        $descricao = preg_replace('/\s/', ' ', $descricao);

        // Se ainda não for a última linha...
        if ($numLinha < count($this->linhas) - 1) {
            // ...verifica se a próxima linha é uma linha completa (DATA DESCRIÇÃO VALOR), ou se é uma linha de complemento da linha anterior
            $linhaComplementar = trim($this->linhas[$numLinha + 1]);
            if ($linhaComplementar && !$this->ehLinhaExtratoSimplesOuSaldo($linhaComplementar)) {
                $this->linhasComplementares[] = $numLinha + 1;
                $descricao .= ' (' . trim($linhaComplementar) . ')';
            }
        }
        $descricao = str_replace('  ', ' ', $descricao);

        $camposLinha['descricao'] = mb_strtoupper($descricao);
        $camposLinha['dtVenctoEfetiva'] = $dtVenctoEfetiva;
        $camposLinha['dtMoviment'] = $dtVenctoEfetiva; // passo o mesmo por se tratar de extrato simples (diferente de extrato de cartão).
        $camposLinha['valor'] = $valor;
        $camposLinha['desconto'] = null;
        $camposLinha['valorTotal'] = $valor;
        $camposLinha['entradaOuSaida'] = $entradaOuSaida;
        $camposLinha['modo'] = null;
        $camposLinha['categoriaCodigo'] = null;
        $camposLinha['planoPagtoCartao'] = null;
        $camposLinha['bandeiraCartao'] = null;

        return $camposLinha;
    }

    /**
     * @return array
     * @throws ViewException
     */
    private function importGrupoMovimentacao(): array
    {
        $movimentacoes = array();

        $i = 0;
        foreach ($this->linhas as $linha) {

            if (!$linha || $linha === TXT_LINHA_NAO_IMPORTADA || $linha === TXT_LINHA_IMPORTADA) {
                continue;
            }

            $camposLinha = $this->importLinhaExtratoSimples($i);

            $descricao = $camposLinha['descricao'];
            $dtMoviment = $camposLinha['dtMoviment'];
            $dtVenctoEfetiva = $camposLinha['dtVenctoEfetiva'];
            $valor = $camposLinha['valor'];
            $desconto = $camposLinha['desconto'];
            $valorTotal = $camposLinha['valorTotal'];

            // Tenta encontrar uma movimentação com as características passadas.
            $movs = $this->repoMovimentacao
                ->findBy([
                    'dtMoviment' => $dtMoviment,
                    'valor' => $valor,
                    'grupoItem' => $this->grupoItem
                ]);

            /** @var Movimentacao $importada */
            $importada = null;
            if ($movs && count($movs) > 0) {
                $importada = $movs[0];
            }

            if ($importada && !$importada->dtPagto) {
                $importada->setStatus('REALIZADA');
                $importada->setDtPagto($dtVenctoEfetiva);
            } else {

                $importada = new Movimentacao();
                $importada->UUID = (StringUtils::guidv4());

                $importada->grupoItem = ($this->grupoItem);

                /** @var Categoria $categ101 */
                $categ101 = $this->repoCategoria->findOneBy(['codigo' => '202001']);  // 2.02.001 - CUSTOS DE MERCADORIAS
                $importada->categoria = ($categ101);

                $importada->centroCusto = ($this->repoCentroCusto->find(1));
                $importada->modo = ($this->repoModo->find(50));

                $importada->valor = ($valor);
                $importada->descontos = ($desconto);
                $importada->valorTotal = ($valorTotal);

                $importada->descricao = (str_replace('  ', ' ', $descricao));
                /** @var TipoLancto $deGrupo */
                $deGrupo = $this->repoTipoLancto->findOneBy(['codigo' => 70]);
                $importada->tipoLancto = ($deGrupo);
                $importada->status = ('REALIZADA');

                $importada->dtMoviment = ($dtMoviment);
                $importada->dtVencto = ($dtVenctoEfetiva);
                $importada->dtVenctoEfetiva = ($dtVenctoEfetiva);
                $importada->dtPagto = ($dtVenctoEfetiva);

                $importada->bandeiraCartao = (null);
            }

            $movimentacoes[] = $importada;
        }

        return $movimentacoes;
    }

    /**
     * @param $numLinha
     * @return array|null
     * @throws ViewException
     */
    private function importLinhaExtratoModerninhaDebito($numLinha)
    {
        /**
         * 0 - Data_Transacao
         * 1 - 'MODERNINHA'
         * 2 - Tipo_Pagamento
         * 3 - Transacao_ID
         * 4 - Valor_Bruto
         */
        $linha = trim($this->linhas[$numLinha]);
        $camposLinha = array();
        $campos = explode("\t", $linha);

        if (count($campos) < 4) {
            return null;
        }

        $dtVenda = DateTimeUtils::parseDateStr($campos[0]);
        $valor = abs(StringUtils::parseFloat($campos[4], true));
        $entradaOuSaida = $valor < 0 ? 2 : 1;

        $descricao = $campos[1] . ' - ' . $campos[3] . ' (' . $campos[2] . ')';
        $descricao = preg_replace('@\n|\r|\t@', '', $descricao);

        $bandeira = 'N INF DÉB';

        $modo = $this->repoModo->find(10); // 'RECEB. CARTÃO DÉBITO'

        $bandeiraCartao = $this->repoBandeiraCartao->findByLabelsAndModo($bandeira, $modo);

        $camposLinha['bandeiraCartao'] = $bandeiraCartao;
        $camposLinha['planoPagtoCartao'] = 'DEBITO';
        $camposLinha['descricao'] = $descricao;
        $camposLinha['dtMoviment'] = $dtVenda;
        $camposLinha['dtVenctoEfetiva'] = $dtVenda;
        $camposLinha['valor'] = $valor;
        $camposLinha['valorTotal'] = $valor;
        $camposLinha['entradaOuSaida'] = $entradaOuSaida;
        $camposLinha['categoriaCodigo'] = 199;

        return $camposLinha;
    }


    private function importLinhaExtratoCieloDebitoNovo($numLinha)
    {
        $linha = trim($this->linhas[$numLinha]);
        $campos = explode("\t", $linha);
        if (count($campos) < 10) {
            return null;
        }

        /**
         * 0 Data da venda
         * 1 Data da autorização
         * 2 Bandeira
         * 3 Forma de pagamento
         * 4 Quantidade de parcelas
         * 5 Valor da venda
         * 6 Taxa de administração (%)
         * 7 Valor descontado
         * 8 Previsão de pagamento
         * 9 Valor líquido da venda
         * 10 Número Lógico
         */

        $dtVenda = DateTimeUtils::parseDateStr($campos[0]);
        $valor = abs(StringUtils::parseFloat($campos[5], true));
        $entradaOuSaida = $valor < 0 ? 2 : 1;
        $descricao = 'DÉBITO ' . $campos[2]; // + ' ' + campos[1] + ' (' + campos[4] + ')';
        $modo = $this->repoModo->find(10); // 'RECEB. CARTÃO DÉBITO'

        $bandeiraCartao = $this->repoBandeiraCartao->findByLabelsAndModo($campos[2], $modo);

        $camposLinha['bandeiraCartao'] = $bandeiraCartao;
        $camposLinha['planoPagtoCartao'] = 'DEBITO';
        $camposLinha['descricao'] = $descricao;
        $camposLinha['dtMoviment'] = $dtVenda;
        $camposLinha['dtVenctoEfetiva'] = $dtVenda;
        $camposLinha['valor'] = $valor;
        $camposLinha['valorTotal'] = $valor;
        $camposLinha['entradaOuSaida'] = $entradaOuSaida;

        return $camposLinha;
    }


    private function importLinhaExtratoCieloCreditoNovo($numLinha)
    {
        $linha = trim($this->linhas[$numLinha]);
        $campos = explode("\t", $linha);
        if (count($campos) < 9) {
            return null;
        }
        /**
         * 0 Data de pagamento
         * 1 Data de venda
         * 2 Forma de pagamento
         * 3 NSU
         * 4 Número do cartão
         * 5 Valor bruto
         * 6 Status
         * 7 Valor líquido
         * 8 TID
         * 9 Taxa
         * 10 Número do EC
         * 11 Bandeira
         */

        $dtVenda = DateTimeUtils::parseDateStr($campos[1]);
        $dtPrevistaPagto = DateTimeUtils::parseDateStr($campos[0]);
        $numeroCartao = trim($campos[4]);
        $tid = trim($campos[8]);
        $codigoAutorizacao = trim($campos[10]);
        $bandeira = trim($campos[11]);
        $formaDePagamento = trim($campos[2]);
        $valor = abs(StringUtils::parseFloat($campos[5], true));

        $entradaOuSaida = $valor < 0 ? 2 : 1;


        $modo = $this->repoModo->find(9); // 'RECEB. CARTÃO CRÉDITO'

        $bandeiraCartao = $this->repoBandeiraCartao->findByLabelsAndModo($bandeira, $modo);

        $planoPagtoCartao = (stripos($formaDePagamento, 'parc') === FALSE) ? 'CREDITO_30DD' : 'CREDITO_PARCELADO';

        $descricao = $formaDePagamento . ' - ' . $bandeira . ' - ' . $numeroCartao . ' (' . $codigoAutorizacao . ') ' . $tid;
        $descricao = str_replace('  ', ' ', $descricao);

        $camposLinha['modo'] = $modo;
        $camposLinha['bandeiraCartao'] = $bandeiraCartao;
        $camposLinha['planoPagtoCartao'] = $planoPagtoCartao;
        $camposLinha['descricao'] = $descricao;
        $camposLinha['dtMoviment'] = $dtVenda;
        $camposLinha['dtVenctoEfetiva'] = $dtPrevistaPagto;
        $camposLinha['valor'] = $valor;
        $camposLinha['valorTotal'] = $valor;
        $camposLinha['entradaOuSaida'] = $entradaOuSaida;
        $camposLinha['categoriaCodigo'] = '101';
        return $camposLinha;
    }


    /**
     * @param $numLinha
     * @return array
     * @throws ViewException
     */
    private function importLinhaExtratoCartaoArrayCabecalho($numLinha)
    {
        $linha = trim($this->linhas[$numLinha]);
        $campos = explode("\t", $linha);

        $camposLinha = [];

        $dtVenda = null;
        $dtPrevistaPagto = null;
        $tipo = null;
        $bandeira = null;
        $valor = null;
        $descricao = null;

        foreach ($this->arrayCabecalho as $campo => $key) {
            switch ($campo) {
                case 'dtVenda':
                {
                    $dtVenda = DateTimeUtils::parseDateStr($campos[$key]);
                    break;
                }
                case 'dtPrevistaPagto':
                {
                    $dtPrevistaPagto = DateTimeUtils::parseDateStr($campos[$key]);
                    break;
                }
                case 'tipo':
                {
                    $tipo = trim($campos[$key]);
                    break;
                }
                case 'bandeira':
                {
                    $bandeira = trim($campos[$key]);
                    break;
                }
                case 'valor':
                {
                    $valor = abs(StringUtils::parseFloat($campos[$key], true));
                    break;
                }
                case 'descricao':
                {
                    if (is_array($key)) {
                        $valores = [];
                        foreach ($key['campos'] as $campoCSV) {
                            $valores[] = trim($campos[$campoCSV]);
                        }
                        $descricao = vsprintf($key['formato'], $valores);
                    } else {
                        $descricao = trim($campos[$key]);
                    }
                    break;
                }
            }
        }

        $entradaOuSaida = $valor < 0 ? 2 : 1;

        /** @var Modo $modo */
        $modo = null;
        if (strpos($this->tipoExtrato, 'CREDITO') !== FALSE) {
            $modo = $this->repoModo->find(9); // 'RECEB. CARTÃO CRÉDITO'
        }
        if (strpos($this->tipoExtrato, 'DEBITO') !== FALSE) {
            $modo = $this->repoModo->find(10); // 'RECEB. CARTÃO DEBITO'
        }

        $bandeiraCartao = null;
        if ($modo && $bandeira) {
            $bandeiraCartao = $this->repoBandeiraCartao->findByLabelsAndModo($bandeira, $modo);
        }

        $planoPagtoCartao = (stripos($descricao, 'parc') === FALSE) ? 'CREDITO_30DD' : 'CREDITO_PARCELADO';

        $camposLinha['modo'] = $modo;
        $camposLinha['bandeiraCartao'] = $bandeiraCartao;
        $camposLinha['planoPagtoCartao'] = $planoPagtoCartao;
        $camposLinha['descricao'] = $descricao;
        $camposLinha['dtMoviment'] = $dtVenda;
        $camposLinha['dtVenctoEfetiva'] = $dtPrevistaPagto;
        $camposLinha['valor'] = $valor;
        $camposLinha['valorTotal'] = $valor;
        $camposLinha['entradaOuSaida'] = $entradaOuSaida;
        $camposLinha['categoriaCodigo'] = '101';
        return $camposLinha;
    }

    /**
     * @param $numLinha
     * @return array
     * @throws ViewException
     */
    private function importLinhaExtratoStoneCredito($numLinha)
    {
        /**
         * 0 CATEGORIA
         * 1 HORA DA VENDA
         * 2 DATA DE VENCIMENTO
         * 3 TIPO
         * 4 Nº DA PARCELA
         * 5 QTD DE PARCELAS
         * 6 BANDEIRA
         * 7 STONE ID
         * 8 N° CARTÃO
         * 9 VALOR BRUTO
         * 10 VALOR LÍQUIDO
         * 11 ÚLTIMO STATUS
         * 12 DATA DO ÚLTIMO STATUS
         */

        if ($this->identificarPorCabecalho) {
            return $this->importLinhaExtratoCartaoArrayCabecalho($numLinha);
        }
        // else

        $linha = trim($this->linhas[$numLinha]);
        $campos = explode("\t", $linha);
        if (count($campos) < 12) {
            return null;
        }

        $dtVenda = DateTimeUtils::parseDateStr($campos[1]);
        $dtPrevistaPagto = DateTimeUtils::parseDateStr($campos[2]);
        $tipo = trim($campos[3]);
        $bandeira = trim($campos[6]);

        $descricao = trim($campos[0]) . ' - ' . $tipo . ' - ' . $bandeira . ' (' . trim($campos[4]) . '/' . trim($campos[5]) . ') ' . trim($campos[8]);

        $valor = abs(StringUtils::parseFloat($campos[9], true));
        $entradaOuSaida = $valor < 0 ? 2 : 1;

        $modo = $this->repoModo->find(9); // 'RECEB. CARTÃO CRÉDITO'

        $bandeiraCartao = $this->repoBandeiraCartao->findByLabelsAndModo($bandeira, $modo);

        $planoPagtoCartao = (stripos($descricao, 'parc') === FALSE) ? 'CREDITO_30DD' : 'CREDITO_PARCELADO';

        $camposLinha['modo'] = $modo;
        $camposLinha['bandeiraCartao'] = $bandeiraCartao;
        $camposLinha['planoPagtoCartao'] = $planoPagtoCartao;
        $camposLinha['descricao'] = $descricao;
        $camposLinha['dtMoviment'] = $dtVenda;
        $camposLinha['dtVenctoEfetiva'] = $dtPrevistaPagto;
        $camposLinha['valor'] = $valor;
        $camposLinha['valorTotal'] = $valor;
        $camposLinha['entradaOuSaida'] = $entradaOuSaida;
        $camposLinha['categoriaCodigo'] = '101';
        return $camposLinha;
    }


    private function importLinhaExtratoStoneDebito($numLinha)
    {

        /**
         * 0 HORA DA VENDA
         * 1 TIPO
         * 2 BANDEIRA
         * 3 MEIO DE CAPTURA
         * 4 STONE ID
         * 5 VALOR BRUTO
         * 6 VALOR LÍQUIDO
         * 7 N° CARTÃO
         * 8 SERIAL NUMBER
         * 9 ÚLTIMO STATUS
         * 10 DATA DO ÚLTIMO STATUS
         */

        $linha = trim($this->linhas[$numLinha]);
        $campos = explode("\t", $linha);
        if (count($campos) < 9) {
            return null;
        }


        try {
            $dtVenda = DateTimeUtils::parseDateStr($campos[0]);
            $valor = abs(StringUtils::parseFloat($campos[5], true));
            $entradaOuSaida = $valor < 0 ? 2 : 1;
            $descricao = $campos[1] . ' - ' . $campos[4] . ' - ' . $campos[7];
            $descricao = preg_replace('@\n|\r|\t@', '', $descricao);
            $modo = $this->repoModo->find(10);// 'RECEB. CARTÃO DÉBITO'
            $bandeiraCartao = $this->repoBandeiraCartao->findByLabelsAndModo($campos[2], $modo);
            $camposLinha['bandeiraCartao'] = $bandeiraCartao;
            $camposLinha['planoPagtoCartao'] = 'DEBITO';
            $camposLinha['descricao'] = $descricao;
            $camposLinha['dtMoviment'] = $dtVenda;
            $camposLinha['dtVenctoEfetiva'] = $dtVenda;
            $camposLinha['valor'] = $valor;
            $camposLinha['valorTotal'] = $valor;
            $camposLinha['entradaOuSaida'] = $entradaOuSaida;
            return $camposLinha;
        } catch (\Exception $e) {
            return null;
        }


    }


    /**
     * @param $movs
     * @param $tipoExtrato
     * @param Carteira|null $carteiraExtrato
     * @param Carteira|null $carteiraDestino
     * @param GrupoItem|null $grupoItem
     */
    public function verificarImportadasAMais($movs, $tipoExtrato, ?Carteira $carteiraExtrato, ?Carteira $carteiraDestino, ?GrupoItem $grupoItem): void
    {
//        /** @var Movimentacao $primeira */
//        $primeira = $movs[0];
//        $dtPagto = $primeira->getDtPagto();
//        $dtIni = DateTimeUtils::getPrimeiroDiaMes($dtPagto);
//        $dtFim = DateTimeUtils::getUltimoDiaMes($dtPagto);
//
//        if (strpos($tipoExtrato, 'DEBITO') !== FALSE) {
//            $dql = 'SELECT m FROM App\Entity\Financeiro\Movimentacao m
//                WHERE
//                m.dtPagto BETWEEN :dtIni AND :dtFim AND
//                m.carteira = :carteiraDestino AND
//                m.modo = :modo AND
//                m.cadeia IN (SELECT m.cadeia FROM App\Entity\Financeiro\Movimentacao m2 WHERE m2.cadeia = m.cadeia && m2.carteira = :carteiraExtrato)';
//
//            $qry = $this->doctrine->createQuery($dql);
//            $qry->setParameter('dtIni', $dtIni);
//            $qry->setParameter('dtFim', $dtFim);
//            $qry->setParameter('carteiraDestino', $carteiraDestino);
//            $qry->setParameter('carteiraExtrato', $carteiraExtrato);
//            $modo = $this->repoModo->find(10); // 'RECEB. CARTÃO DEBITO'
//            $qry->setParameter('modo', $modo);
//            $rs = $qry->getResult();
//// FIXME: terminar
//
//        }

    }

    /**
     * @param Movimentacao $movimentacao
     * @return bool
     */
    private function checkJaImportada(Movimentacao $movimentacao): bool
    {
        if ($movimentacao->getId()) {
            /** @var Movimentacao $movsJaImportada */
            foreach ($this->movsJaImportadas as $movsJaImportada) {
                if ($movsJaImportada->getId() === $movimentacao->getId()) {
                    return true;
                }
            }
        }
        return false;
    }

}
