<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Entity\Base\DiaUtil;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\Base\DiaUtilRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\ExceptionUtils\ExceptionUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\NumberUtils\DecimalUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Banco;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\BandeiraCartao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Cadeia;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CentroCusto;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Fatura;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\OperadoraCartao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\TipoLancto;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\ModoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\TipoLanctoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @author Carlos Eduardo Pauluk
 */
class MovimentacaoEntityHandler extends EntityHandler
{

    public FaturaEntityHandler $faturaEntityHandler;

    private LoggerInterface $logger;

    /**
     *
     * @param EntityManagerInterface $doctrine
     * @param Security $security
     * @param ParameterBagInterface $parameterBag
     * @param SyslogBusiness $syslog
     * @param FaturaEntityHandler $faturaEntityHandler
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $doctrine,
                                Security               $security,
                                ParameterBagInterface  $parameterBag,
                                SyslogBusiness         $syslog,
                                FaturaEntityHandler    $faturaEntityHandler,
                                LoggerInterface        $logger)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog->setApp('radx')->setComponent(self::class));
        $this->faturaEntityHandler = $faturaEntityHandler;
        $this->logger = $logger;
    }


    /**
     * Descrição das regras em http://docs.crosier.com.br/books/finan/page/regras-para-movimenta%C3%A7%C3%B5es/edit
     *
     *
     * @param Movimentacao $movimentacao
     * @return Movimentacao
     * @throws ViewException
     */
    public function beforeSave($movimentacao)
    {
        $repoCarteira = $this->doctrine->getRepository(Carteira::class);
        $repoModo = $this->doctrine->getRepository(Modo::class);
        $repoTipoLancto = $this->doctrine->getRepository(TipoLancto::class);
        
        $movimentacao->tipoLancto = $movimentacao->tipoLancto ?? $repoTipoLancto->findOneByCodigo(20);
        
        if ($movimentacao->grupoItem) {
            $movimentacao->carteira = $repoCarteira->findOneByCodigo(50);
            $movimentacao->modo = $repoModo->findOneByCodigo(50);
            $movimentacao->valor = $movimentacao->valorTotal;
        }

        if (!$movimentacao->carteira) {
            if ($movimentacao->operadoraCartao) {
                $movimentacao->carteira = $movimentacao->operadoraCartao->carteira;
            } else {
                throw new ViewException('Campo "Carteira" precisa ser informado');
            }
        }
        if (!$movimentacao->modo) {
            throw new ViewException('Campo "Modo" precisa ser informado');
        }
        if (!$movimentacao->categoria) {
            throw new ViewException('Campo "Categoria" precisa ser informado');
        }
        if ('' === trim($movimentacao->descricao)) {
            throw new ViewException('Campo "Descrição" precisa ser informado');
        }

        if (!$movimentacao->UUID) {
            $movimentacao->UUID = StringUtils::guidv4();
        }

        if (!$movimentacao->centroCusto) {
            /** @var CentroCusto $centroCusto */
            $centroCusto = $this->doctrine->getRepository(CentroCusto::class)->find(1); // 1,'GLOBAL'
            $movimentacao->centroCusto = $centroCusto;
        }


        if (in_array($movimentacao->tipoLancto->codigo, [60, 61], true)) {
            $movimentacao->dtVencto = clone($movimentacao->dtMoviment);
            $movimentacao->dtVenctoEfetiva = clone($movimentacao->dtMoviment);
            $movimentacao->dtPagto = clone($movimentacao->dtMoviment);
        }

        // Regras Gerais
        $movimentacao->descricao = trim(preg_replace('/\t+/', '', $movimentacao->descricao));

        if ($movimentacao->modo->getCodigo() === 50) { // 50,'MOVIMENTAÇÃO AGRUPADA'
            if (!$movimentacao->grupoItem) {
                throw new ViewException('Campo "Grupo Item" precisa ser informado');
            }

            $movimentacao->dtVencto = clone($movimentacao->grupoItem->dtVencto);
            $movimentacao->dtVenctoEfetiva = clone($movimentacao->grupoItem->dtVencto);
            $movimentacao->dtPagto = clone($movimentacao->grupoItem->dtVencto);
        }


        if ($movimentacao->carteira->caixa) {
            $movimentacao->dtPagto = clone($movimentacao->dtMoviment);
            $movimentacao->dtVencto = clone($movimentacao->dtMoviment);
            $movimentacao->dtVenctoEfetiva = clone($movimentacao->dtMoviment);
        }


        if ($movimentacao->dtPagto && !$movimentacao->dtMoviment) {
            $movimentacao->dtMoviment = $movimentacao->dtPagto;
        }


        if (!$movimentacao->dtVencto) {
            $movimentacao->dtVencto = clone($movimentacao->dtMoviment);
        }

        // Regras para Datas
        if (!$movimentacao->dtPagto) {
            $movimentacao->status = 'ABERTA';
        } else {
            $movimentacao->status = 'REALIZADA';
            if (!$movimentacao->dtVencto) {
                $movimentacao->dtVencto = clone($movimentacao->dtPagto);
            }
            if (!$movimentacao->dtMoviment) {
                $movimentacao->dtMoviment = clone($movimentacao->dtPagto);
            }
        }

        if (!$movimentacao->dtVencto) {
            throw new ViewException('Campo "Dt Vencto" precisa ser informado');
        }
        if (!$movimentacao->dtMoviment) {
            throw new ViewException('Campo "Dt Moviment" precisa ser informado');
        }
        if (!$movimentacao->dtVenctoEfetiva) {
            /** @var DiaUtilRepository $repoDiaUtil */
            $repoDiaUtil = $this->doctrine->getRepository(DiaUtil::class);
            $proxDiaUtilFinanceiro = $repoDiaUtil->findDiaUtil($movimentacao->dtVencto, null, true);
            $movimentacao->dtVenctoEfetiva = clone($proxDiaUtilFinanceiro);
        }
        $movimentacao->dtUtil = clone($movimentacao->dtPagto ?? $movimentacao->dtVenctoEfetiva);


        // Por enquanto...
        if (!$movimentacao->quitado) {
            $movimentacao->quitado = ($movimentacao->status === 'REALIZADA');
        }


        // Regras para valores
        $movimentacao->valor = abs($movimentacao->valor ?? 0.0);
        $movimentacao->descontos = (-1 * abs($movimentacao->descontos ?? 0.0));
        $movimentacao->acrescimos = abs($movimentacao->acrescimos ?? 0.0);
        $movimentacao->calcValorTotal();


        // Regras para Status
        if ($movimentacao->status === 'REALIZADA') {
            if (!$movimentacao->carteira->concreta) {
                throw new ViewException('Somente carteiras concretas podem conter movimentações com status "REALIZADA"');
            }
            if ($movimentacao->modo->getCodigo() === 99 && !in_array($movimentacao->categoria->codigo, [195, 295], true)) {
                throw new ViewException('Não é possível salvar uma movimentação com status "REALIZADA" em modo 99 (INDEFINIDO)');
            }
        } else { // if ($movimentacao->getStatus() === 'ABERTA') {
            if (!$movimentacao->carteira->abertas) {
                throw new ViewException('Esta carteira não pode conter movimentações com status "ABERTA".');
            }
        }

        // Regras para movimentações de cartões
        if (FALSE === $movimentacao->modo->modoDeCartao) {
            $movimentacao->qtdeParcelas = null;
            $movimentacao->bandeiraCartao = null;
            $movimentacao->operadoraCartao = null;
        } else {
            if ($movimentacao->carteira->operadoraCartao) {
                // $this->doctrine->refresh($movimentacao->carteira->operadoraCartao));
                $movimentacao->operadoraCartao = $movimentacao->carteira->operadoraCartao;
            }
            if ($movimentacao->bandeiraCartao) {

                if (!trim($movimentacao->descricao)) {
                    $movimentacao->descricao = $movimentacao->bandeiraCartao->descricao;
                }

                if ($movimentacao->bandeiraCartao->modo->getId() !== $movimentacao->modo->getId()) {
                    throw new ViewException(
                        vsprintf(
                            'Bandeira de cartão selecionada para o modo %s (%s), porém a movimentação foi informada como sendo %s',
                            [$movimentacao->bandeiraCartao->modo->descricao,
                                $movimentacao->bandeiraCartao->descricao,
                                $movimentacao->modo->descricao]));
                }
            }
        }

        

        // Regras para movimentações com cheque
        if (FALSE === $movimentacao->modo->modoDeCheque) {
            $movimentacao->chequeNumCheque = null;
            $movimentacao->chequeAgencia = null;
            $movimentacao->chequeBanco = null;
            $movimentacao->chequeConta = null;
        } elseif ($movimentacao->modo->codigo === 3) {
            $movimentacao->chequeAgencia = $movimentacao->carteira->agencia;
            $movimentacao->chequeBanco = $movimentacao->carteira->banco;
            $movimentacao->chequeConta = $movimentacao->carteira->conta;
        }

        // Regras para movimentações recorrentes
        if (!$movimentacao->recorrente) {
            $movimentacao->recorrente = false;
        }


        // Trava para Dt Consolidado
        if ($movimentacao->dtPagto) {
            $dtPagto = (clone($movimentacao->dtPagto))->setTime(0, 0);
            $dtConsolidado_carteira = (clone($movimentacao->carteira->dtConsolidado))->setTime(0, 0);
            if ($dtPagto <= $dtConsolidado_carteira) {
                throw new ViewException('Carteira ' . $movimentacao->carteira->descricao . ' está consolidada em ' . $movimentacao->carteira->dtConsolidado->format('d/m/Y'));
            }
            if ($movimentacao->carteiraDestino) {
                $dtConsolidado_carteiraDestino = (clone($movimentacao->carteira->dtConsolidado))->setTime(0, 0);
                if ($dtPagto <= $dtConsolidado_carteiraDestino) {
                    throw new ViewException('Carteira ' . $movimentacao->carteiraDestino->descricao . ' está consolidada em ' . $movimentacao->carteiraDestino->dtConsolidado->format('d/m/Y'));
                }
            }
        }

        return $movimentacao;
    }

    /**
     * @param array|ArrayCollection $movs
     * @param bool $todasNaMesmaCadeia
     * @throws ViewException
     */
    public function saveAll($movs, bool $todasNaMesmaCadeia = false): void
    {
        try {
            $this->doctrine->beginTransaction();

            $cadeia = null;
            if ($todasNaMesmaCadeia) {
                /** @var Movimentacao $primeira */
                foreach ($movs as $key => $primeira) {
                    // RTA para pegar o primeiro elemento do array
                    break;
                }

                $cadeia = null;
                if ($primeira->cadeia && !$primeira->cadeia->getId()) {
                    $cadeia = $primeira->cadeia;
                    $cadeia->movimentacoes = null;
                    $this->faturaEntityHandler->cadeiaEntityHandler->save($cadeia);
                }
            }
            /** @var Movimentacao $mov */
            foreach ($movs as $mov) {
                if ($cadeia) {
                    $mov->cadeia = $cadeia;
                }
                $this->refindAll($mov);
                $this->save($mov);
                $this->doctrine->clear();
            }
            $this->doctrine->commit();
        } catch (ViewException | \Throwable $e) {
            $this->logger->error('Erro no saveAll()');
            $this->logger->error($e->getMessage());
            $this->doctrine->clear();
            $this->doctrine->rollback();
            $err = 'Erro ao salvar movimentações';
            if ($e instanceof ViewException) {
                $err = ExceptionUtils::treatException($e);
            }
            if (isset($mov)) {
                $err .= ' (' . $mov->descricao . ')';
            }
            throw new ViewException($err);
        }
    }

    /**
     * Tratamento diferenciado para cada tipoLancto.
     *
     * @param EntityId $movimentacao
     * @param bool $flush
     * @return EntityId|Movimentacao|null|object
     * @throws ViewException
     */
    public function save(EntityId $movimentacao, $flush = true)
    {
        /** @var TipoLanctoRepository $repoTipoLancto */
        $repoTipoLancto = $this->doctrine->getRepository(TipoLancto::class);
        /** @var Movimentacao $movimentacao */
        if (!$movimentacao->tipoLancto) {
            $movimentacao->tipoLancto = $repoTipoLancto->findOneBy(['codigo' => 20]);
        }

        // 60 - TRANSFERÊNCIA ENTRE CARTEIRAS
        if ($movimentacao->tipoLancto->getCodigo() === 60) {
            return $this->saveTransfPropria($movimentacao);
        }

        // 61 - TRANSFERÊNCIA DE ENTRADA DE CAIXA
        if ($movimentacao->tipoLancto->getCodigo() === 61) {
            return $this->saveTransfEntradaCaixa($movimentacao);
        }

        // 62 - ENTRADA POR CARTÃO DE CRÉDITO
        if ($movimentacao->tipoLancto->getCodigo() === 63 && !$movimentacao->getId()) {
            return $this->saveEntradaCartaoDeCreditoOuDebito($movimentacao);
        }

        // 64 - ENTRADA DE CAIXA POR TRANSF. BANCÁRIA
        if ($movimentacao->tipoLancto->getCodigo() === 64 && !$movimentacao->getId()) {
            return $this->saveEntradaDeCaixaPorTransfBancaria($movimentacao);
        }

        if ($movimentacao->jsonData['dadosParcelamento'] ?? false) {
            return $this->saveParcelamento($movimentacao);
        }

        // else
        return parent::save($movimentacao);

    }

    /**
     * Salva uma transferência entre carteiras.
     *
     * A $movimentacao passada não deverá ser 199 ou 299.
     *
     * @param Movimentacao $movimentacao
     * @return Movimentacao
     * @throws ViewException
     */
    public function saveTransfPropria(Movimentacao $movimentacao): Movimentacao
    {
        $this->getDoctrine()->beginTransaction();

        if (!$movimentacao->modo) {
            /** @var ModoRepository $repoModo */
            $repoModo = $this->doctrine->getRepository(Modo::class);
            $modo = $repoModo->findOneByCodigo(11);
            $movimentacao->modo = $modo;
        }

        /** @var TipoLanctoRepository $repoTipoLancto */
        $repoTipoLancto = $this->doctrine->getRepository(TipoLancto::class);
        $tipoLancto_transferenciaEntreCarteiras = $repoTipoLancto->findOneBy(['codigo' => 60]);
        $movimentacao->tipoLancto = $tipoLancto_transferenciaEntreCarteiras;

        /** @var Categoria $categ299 */
        $categ299 = $this->doctrine->getRepository(Categoria::class)->findOneBy(['codigo' => 299]);
        /** @var Categoria $categ199 */
        $categ199 = $this->doctrine->getRepository(Categoria::class)->findOneBy(['codigo' => 199]);

        if (!in_array($movimentacao->categoria->codigo, [199, 299], true)) {
            throw new ViewException('Apenas movimentações 1.99 ou 2.99 podem ser salvas.');
        }

        $categOposta = $movimentacao->categoria->codigo === 199 ? $categ299 : $categ199;

        // Está editando
        if ($movimentacao->getId()) {

            if ($movimentacao->cadeia && $movimentacao->cadeia->movimentacoes &&
                $movimentacao->cadeia->movimentacoes->count() !== 0 &&
                $movimentacao->cadeia->movimentacoes->count() !== 2) {
                throw new ViewException('Apenas cadeias com 2 podem ser editadas ("TRANSFERÊNCIA ENTRE CARTEIRAS")');
            }
            /** @var Movimentacao $movimentOposta */
            $movimentOposta = $this->getDoctrine()->getRepository(Movimentacao::class)
                ->findOneBy(
                    [
                        'cadeia' => $movimentacao->cadeia,
                        'categoria' => $categOposta
                    ]);

            // Campos que podem ser editados
            $movimentOposta->descricao = $movimentacao->descricao;
            $movimentOposta->fatura = $movimentacao->fatura;
            $movimentOposta->categoria = ($categOposta);
            $movimentOposta->modo = ($movimentacao->modo);
            if ($movimentacao->carteiraDestino) {
                $movimentOposta->carteira = ($movimentacao->carteiraDestino);
            }
            $movimentOposta->carteiraDestino = ($movimentacao->carteira);
            $movimentOposta->valor = ($movimentacao->valor);
            $movimentOposta->valorTotal = ($movimentacao->valorTotal);
            $movimentOposta->centroCusto = ($movimentacao->centroCusto);
            $movimentOposta->dtMoviment = ($movimentacao->dtMoviment);
            $movimentOposta->dtVencto = ($movimentacao->dtVencto);
            $movimentOposta->dtVenctoEfetiva = ($movimentacao->dtVenctoEfetiva);
            $movimentOposta->dtPagto = ($movimentacao->dtPagto);

            /** @var Movimentacao $movimentOposta */
            $movimentOposta = parent::save($movimentOposta);

            $movimentacao->carteiraDestino = $movimentOposta->carteira;
            $movimentacao = parent::save($movimentacao);
            $this->getDoctrine()->commit();
            /** @var Movimentacao $movimentacao */
            return $movimentacao;
        }
        // else

        $cadeia = new Cadeia();
        $cadeia->fechada = true;
        $cadeia->vinculante = true;
        /** @var Cadeia $cadeia */
        $cadeia = $this->faturaEntityHandler->cadeiaEntityHandler->save($cadeia);

        $cadeiaOrdem = $movimentacao->categoria->codigo === 299 ? 1 : 2;
        $movimentacao->cadeia = $cadeia;
        $movimentacao->cadeiaOrdem = $cadeiaOrdem;
        $movimentacao->cadeiaQtde = 2;

        $cadeiaOrdemOposta = $movimentacao->categoria->codigo === 299 ? 2 : 1;

        /** @var Movimentacao $movimentOposta */
        $movimentOposta = $this->cloneEntityId($movimentacao);
        $movimentOposta->carteira = $movimentacao->carteiraDestino;
        $movimentOposta->carteiraDestino = $movimentacao->carteira;
        $movimentOposta->cadeiaOrdem = $cadeiaOrdemOposta;
        $movimentOposta->cadeiaQtde = 2;
        $movimentOposta->categoria = $categOposta;
        $movimentOposta->status = 'REALIZADA';

        parent::save($movimentOposta);
        parent::save($movimentacao);
        $this->getDoctrine()->commit();

        return $movimentacao;
    }


    /**
     * Salva uma transferência de entrada de caixa. Uma cadeia com 3 movimentações:
     * 101 - na carteira do caixa
     * 299 - na carteira do caixa
     * 199 - na carteira destino
     *
     * @param Movimentacao $movimentacao
     * @return Movimentacao
     * @throws ViewException
     */
    public function saveTransfEntradaCaixa(Movimentacao $movimentacao): Movimentacao
    {
        $this->getDoctrine()->beginTransaction();

        /** @var TipoLanctoRepository $repoTipoLancto */
        $repoTipoLancto = $this->doctrine->getRepository(TipoLancto::class);
        $tipoLancto_transferenciaEntradaDeCaixa = $repoTipoLancto->findOneBy(['codigo' => 61]);
        $movimentacao->tipoLancto = $tipoLancto_transferenciaEntradaDeCaixa;


        /** @var Categoria $categ299 */
        $categ299 = $this->doctrine->getRepository(Categoria::class)->findOneBy(['codigo' => 299]);
        /** @var Categoria $categ199 */
        $categ199 = $this->doctrine->getRepository(Categoria::class)->findOneBy(['codigo' => 199]);

        // Está editando
        if ($movimentacao->getId()) {
            if ($movimentacao->cadeia->movimentacoes->count() !== 3) {
                throw new ViewException('Apenas cadeias com 3 movimentações podem ser editadas (TRANSFERÊNCIA DE ENTRADA DE CAIXA).');
            }

            $movs = $movimentacao->cadeia->movimentacoes;
            $outraMov = null;
            /** @var Movimentacao $mov */
            foreach ($movs as $mov) {
                if ($mov->getId() !== $movimentacao->getId()) {
                    $mov->descricao = $movimentacao->descricao;
                    $mov->fatura = $movimentacao->fatura;
                    $mov->categoria = $categ299;
                    $mov->modo = $movimentacao->modo;
                    $mov->valor = $movimentacao->valor;
                    $mov->valorTotal = $movimentacao->valorTotal;
                    $mov->centroCusto = $movimentacao->centroCusto;
                    $mov->dtMoviment = clone($movimentacao->dtMoviment);
                    $mov->dtVencto = clone($movimentacao->dtVencto);
                    $mov->dtVenctoEfetiva = clone($movimentacao->dtVenctoEfetiva);
                    $mov->dtPagto = clone($movimentacao->dtPagto);
                    $mov->cadeiaQtde = 3;
                    parent::save($mov);
                }
            }

            /** @var Movimentacao $movimentacao */
            $movimentacao = parent::save($movimentacao);

            $this->getDoctrine()->commit();
            return $movimentacao;
        }
        // else

        if (!in_array($movimentacao->categoria->codigo, [101, 102], true)) {
            throw new ViewException('TRANSFERÊNCIA DE ENTRADA DE CAIXA precisa ser lançada a partir de uma movimentação de categoria 1.01 ou 1.02');

        }

        $cadeia = new Cadeia();
        $cadeia->fechada = true;
        $cadeia->vinculante = true;
        /** @var Cadeia $cadeia */
        $cadeia = $this->faturaEntityHandler->cadeiaEntityHandler->save($cadeia);

        $movimentacao->cadeia = $cadeia;
        $movimentacao->cadeiaOrdem = 1;

        $moviment299 = new Movimentacao();
        $moviment299->tipoLancto = $movimentacao->tipoLancto;
        $moviment299->fatura = $movimentacao->fatura;
        $moviment299->cadeia = $cadeia;
        $moviment299->cadeiaOrdem = 2;
        $moviment299->cadeiaQtde = 3;
        $moviment299->descricao = $movimentacao->descricao;
        $moviment299->categoria = $categ299;
        $moviment299->centroCusto = $movimentacao->centroCusto;
        $moviment299->modo = $movimentacao->modo;
        $moviment299->carteira = $movimentacao->carteira;
        $moviment299->carteiraDestino = $movimentacao->carteiraDestino;
        $moviment299->status = 'REALIZADA';
        $moviment299->valor = $movimentacao->valor;
        $moviment299->descontos = $movimentacao->descontos;
        $moviment299->acrescimos = $movimentacao->acrescimos;
        $moviment299->valorTotal = $movimentacao->valorTotal;

        $moviment299->dtMoviment = clone($movimentacao->dtMoviment);
        $moviment299->dtVencto = clone($movimentacao->dtMoviment);
        $moviment299->dtVenctoEfetiva = clone($movimentacao->dtMoviment);
        $moviment299->dtPagto = clone($movimentacao->dtMoviment);

        $moviment299->tipoLancto = $movimentacao->tipoLancto;
        parent::save($moviment299);

        $moviment199 = new Movimentacao();
        $moviment199->tipoLancto = $movimentacao->tipoLancto;
        $moviment199->fatura = $movimentacao->fatura;
        $moviment199->cadeia = $cadeia;
        $moviment199->cadeiaOrdem = 3;
        $moviment199->cadeiaQtde = 3;
        $moviment199->descricao = $movimentacao->descricao;
        $moviment199->categoria = $categ199;
        $moviment199->centroCusto = $movimentacao->centroCusto;
        $moviment199->modo = $movimentacao->modo;
        $moviment199->carteira = $movimentacao->carteiraDestino;
        $moviment199->carteiraDestino = $movimentacao->carteira;
        $moviment199->status = 'REALIZADA';
        $moviment199->valor = $movimentacao->valor;
        $moviment199->descontos = $movimentacao->descontos;
        $moviment199->acrescimos = $movimentacao->acrescimos;
        $moviment199->valorTotal = $movimentacao->valorTotal;

        $moviment199->dtMoviment = clone($movimentacao->dtMoviment);
        $moviment199->dtVencto = clone($movimentacao->dtMoviment);
        $moviment199->dtVenctoEfetiva = clone($movimentacao->dtMoviment);
        $moviment199->dtPagto = clone($movimentacao->dtMoviment);

        $moviment199->tipoLancto = $movimentacao->tipoLancto;
        parent::save($moviment199);

        parent::save($movimentacao);
        $this->getDoctrine()->commit();

        return $movimentacao;
    }


    /**
     * @return Movimentacao
     * @throws ViewException
     */
    private function saveEntradaDeCaixaPorTransfBancaria(Movimentacao $movimentacao): Movimentacao
    {
        if (!$movimentacao->carteiraDestino) {
            throw new ViewException('Carteira destino n/d');
        }

        $this->getDoctrine()->beginTransaction();

        /** @var Categoria $categ291 */
        $categ291 = $this->doctrine->getRepository(Categoria::class)->findOneBy(['codigo' => 291]);
        /** @var Categoria $categ191 */
        $categ191 = $this->doctrine->getRepository(Categoria::class)->findOneBy(['codigo' => 191]);

        $faturaOrdem = null;
        if ($movimentacao->fatura && !$movimentacao->faturaOrdem) {
            $faturaOrdem = 1;
        }

        // Está editando
        if ($movimentacao->getId()) {

            $movs = $movimentacao->cadeia->movimentacoes;
            $outraMov = null;
            /** @var Movimentacao $mov */
            foreach ($movs as $mov) {
                if ($mov->getId() !== $movimentacao->getId()) {
                    $mov->descricao = $movimentacao->descricao;
                    $mov->fatura = $movimentacao->fatura;
                    $mov->categoria = $categ291;
                    $mov->modo = $movimentacao->modo;
                    $mov->valor = $movimentacao->valor;
                    $mov->valorTotal = $movimentacao->valorTotal;
                    $mov->centroCusto = $movimentacao->centroCusto;
                    $mov->dtMoviment = clone($movimentacao->dtMoviment);
                    $mov->dtVencto = clone($movimentacao->dtVencto);
                    $mov->dtVenctoEfetiva = clone($movimentacao->dtVenctoEfetiva);
                    $mov->dtPagto = clone($movimentacao->dtPagto);
                    $mov->cadeiaQtde = 3;
                    $mov->jsonData = $movimentacao->jsonData;
                    parent::save($mov);
                }
            }

            /** @var Movimentacao $movimentacao */
            $movimentacao = parent::save($movimentacao);

            $this->getDoctrine()->commit();
            return $movimentacao;
        }
        // else

        if (!in_array($movimentacao->categoria->codigo, [101, 102, 110], true)) {
            throw new ViewException('Movimentação de entrada precisa ser lançada a partir de uma movimentação de categorias 1.01, 1.02 ou 1.10');
        }

        $cadeia = new Cadeia();
        $cadeia->fechada = true;
        $cadeia->vinculante = true;
        /** @var Cadeia $cadeia */
        $cadeia = $this->faturaEntityHandler->cadeiaEntityHandler->save($cadeia);

        $movimentacao->cadeia = $cadeia;
        $movimentacao->cadeiaOrdem = 1;
        $movimentacao->cadeiaQtde = 3;

        $moviment291 = new Movimentacao();
        $moviment291->tipoLancto = $movimentacao->tipoLancto;
        $moviment291->fatura = $movimentacao->fatura;
        $moviment291->faturaOrdem = $faturaOrdem ? ++$faturaOrdem : null;
        $moviment291->cadeia = $cadeia;
        $moviment291->cadeiaOrdem = 2;
        $moviment291->cadeiaQtde = 3;
        $moviment291->descricao = $movimentacao->descricao;
        $moviment291->categoria = $categ291;
        $moviment291->centroCusto = $movimentacao->centroCusto;
        $moviment291->modo = $movimentacao->modo;
        $moviment291->carteira = $movimentacao->carteira;
        $moviment291->carteiraDestino = $movimentacao->carteiraDestino;
        $moviment291->status = 'REALIZADA';
        $moviment291->valor = $movimentacao->valor;
        $moviment291->descontos = $movimentacao->descontos;
        $moviment291->acrescimos = $movimentacao->acrescimos;
        $moviment291->valorTotal = $movimentacao->valorTotal;
        $moviment291->dtMoviment = clone($movimentacao->dtMoviment);
        $moviment291->dtVencto = clone($movimentacao->dtMoviment);
        $moviment291->dtVenctoEfetiva = clone($movimentacao->dtMoviment);
        $moviment291->dtPagto = clone($movimentacao->dtMoviment);

        $moviment291->tipoLancto = $movimentacao->tipoLancto;
        $moviment291->jsonData = $movimentacao->jsonData;
        parent::save($moviment291);

        $moviment191 = new Movimentacao();
        $moviment191->tipoLancto = $movimentacao->tipoLancto;
        $moviment191->fatura = $movimentacao->fatura;
        $moviment191->cadeia = $cadeia;
        $moviment191->cadeiaOrdem = 3;
        $moviment191->faturaOrdem = 3;
        $moviment191->cadeiaQtde = 3;
        $moviment191->descricao = $movimentacao->descricao;
        $moviment191->categoria = $categ191;
        $moviment191->centroCusto = $movimentacao->centroCusto;
        $moviment191->modo = $movimentacao->modo;
        $moviment191->carteira = $movimentacao->carteiraDestino;
        $moviment191->carteiraDestino = $movimentacao->carteira;
        $moviment191->status = 'ABERTA'; // se torna 'REALIZADA' na consolidação do extrato
        $moviment191->valor = $movimentacao->valor;
        $moviment191->descontos = $movimentacao->descontos;
        $moviment191->acrescimos = $movimentacao->acrescimos;
        $moviment191->valorTotal = $movimentacao->valorTotal;

        $moviment191->dtMoviment = clone($movimentacao->dtMoviment);
        $moviment191->dtVencto = clone($movimentacao->dtMoviment);
        $moviment191->dtVenctoEfetiva = clone($moviment191->dtMoviment);

        $moviment191->tipoLancto = $movimentacao->tipoLancto;
        $moviment191->jsonData = $movimentacao->jsonData;
        parent::save($moviment191);


        parent::save($movimentacao);
        $this->getDoctrine()->commit();

        return $movimentacao;
    }


    /**
     *
     * @param Movimentacao $movimentacao
     * @return Movimentacao
     * @throws ViewException
     */
    private function saveEntradaCartaoDeCreditoOuDebito(Movimentacao $movimentacao): Movimentacao
    {
        if (!$movimentacao->carteira || !$movimentacao->operadoraCartao) {
            throw new ViewException('Movimentação de cartão precisa ter carteira e operadora de cartão');
        }

        $this->getDoctrine()->beginTransaction();

        /** @var Categoria $categ291 */
        $categ291 = $this->doctrine->getRepository(Categoria::class)->findOneBy(['codigo' => 291]);
        /** @var Categoria $categ191 */
        $categ191 = $this->doctrine->getRepository(Categoria::class)->findOneBy(['codigo' => 191]);

        $faturaOrdem = null;
        if ($movimentacao->fatura && !$movimentacao->faturaOrdem) {
            $faturaOrdem = 1;
        }

        $ehDebito = $movimentacao->modo->codigo === 10;

        $qtdeParcelas = $ehDebito ? 1 : $movimentacao->qtdeParcelas;
        $cadeiaQtde = $qtdeParcelas + 2; // 101 + 291 + 191s...

        // Está editando
        if ($movimentacao->getId()) {

            $movs = $movimentacao->cadeia->movimentacoes;
            $outraMov = null;
            /** @var Movimentacao $mov */
            foreach ($movs as $mov) {
                if ($mov->getId() !== $movimentacao->getId()) {
                    $mov->descricao = $movimentacao->descricao;
                    $mov->fatura = $movimentacao->fatura;
                    $mov->categoria = $categ291;
                    $mov->modo = $movimentacao->modo;
                    $mov->valor = $movimentacao->valor;
                    $mov->valorTotal = $movimentacao->valorTotal;
                    $mov->centroCusto = $movimentacao->centroCusto;
                    $mov->dtMoviment = clone($movimentacao->dtMoviment);
                    $mov->dtVencto = clone($movimentacao->dtVencto);
                    $mov->dtVenctoEfetiva = clone($movimentacao->dtVenctoEfetiva);
                    $mov->dtPagto = clone($movimentacao->dtPagto);
                    $mov->cadeiaQtde = $cadeiaQtde;
                    $mov->jsonData = $movimentacao->jsonData;
                    parent::save($mov);
                }
            }

            /** @var Movimentacao $movimentacao */
            $movimentacao = parent::save($movimentacao);

            $this->getDoctrine()->commit();
            return $movimentacao;
        }
        // else

        if (!in_array($movimentacao->categoria->codigo, [101, 102, 110], true)) {
            throw new ViewException('Entrada por cartão precisa ser lançada a partir de uma movimentação de categorias 1.01, 1.02 ou 1.10');
        }

        $cadeia = new Cadeia();
        $cadeia->fechada = true;
        $cadeia->vinculante = true;
        /** @var Cadeia $cadeia */
        $cadeia = $this->faturaEntityHandler->cadeiaEntityHandler->save($cadeia);

        $movimentacao->cadeia = $cadeia;
        $movimentacao->cadeiaOrdem = 1;
        $movimentacao->cadeiaQtde = $cadeiaQtde;
        

        $moviment291 = new Movimentacao();
        $moviment291->tipoLancto = $movimentacao->tipoLancto;
        $moviment291->fatura = $movimentacao->fatura;
        $moviment291->faturaOrdem = $faturaOrdem ? ++$faturaOrdem : null;
        $moviment291->cadeia = $cadeia;
        $moviment291->cadeiaOrdem = 2;
        $moviment291->cadeiaQtde = $cadeiaQtde;
        $moviment291->descricao = $movimentacao->descricao;
        $moviment291->categoria = $categ291;
        $moviment291->centroCusto = $movimentacao->centroCusto;
        $moviment291->modo = $movimentacao->modo;
        $moviment291->carteira = $movimentacao->carteira;
        $moviment291->carteiraDestino = $movimentacao->operadoraCartao->carteira;
        $moviment291->status = 'REALIZADA';
        $moviment291->valor = $movimentacao->valor;
        $moviment291->descontos = $movimentacao->descontos;
        $moviment291->acrescimos = $movimentacao->acrescimos;
        $moviment291->valorTotal = $movimentacao->valorTotal;
        $moviment291->dtMoviment = clone($movimentacao->dtMoviment);
        $moviment291->dtVencto = clone($movimentacao->dtMoviment);
        $moviment291->dtVenctoEfetiva = clone($movimentacao->dtMoviment);
        $moviment291->dtPagto = clone($movimentacao->dtMoviment);

        $moviment291->tipoLancto = $movimentacao->tipoLancto;
        $moviment291->jsonData = $movimentacao->jsonData;
        parent::save($moviment291);

        $primeiraDtVencto = $ehDebito ?
            (clone $movimentacao->dtMoviment)->add(new \DateInterval('P1D')) :
            (clone $movimentacao->dtMoviment)->add(new \DateInterval('P1M'));

        $parcelas = DecimalUtils::gerarParcelas($movimentacao->valor, $qtdeParcelas);

        for ($i = 1; $i <= $qtdeParcelas; $i++) {
            $moviment191 = new Movimentacao();
            $moviment191->tipoLancto = $movimentacao->tipoLancto;
            $moviment191->fatura = $movimentacao->fatura;
            $moviment191->cadeia = $cadeia;
            $moviment191->qtdeParcelas = $qtdeParcelas;
            $moviment191->parcelamento = true;
            $moviment191->parcelaNum = $i;
            $moviment191->cadeiaOrdem = 2 + $i;
            $moviment191->faturaOrdem = $faturaOrdem ? ++$faturaOrdem : null;
            $moviment191->cadeiaQtde = $cadeiaQtde;
            $moviment191->descricao = $movimentacao->descricao;
            $moviment191->categoria = $categ191;
            $moviment191->centroCusto = $movimentacao->centroCusto;
            $moviment191->modo = $movimentacao->modo;
            $moviment191->operadoraCartao = $movimentacao->operadoraCartao;
            $moviment191->carteira = $movimentacao->operadoraCartao->carteira;
            $moviment191->carteiraDestino = $movimentacao->carteira; // a oposta
            $moviment191->status = 'ABERTA'; // se torna 'REALIZADA' na consolidação do extrato
            $moviment191->valor = $parcelas[$i-1];
            $moviment191->valorTotal = $parcelas[$i-1];

            $moviment191->dtMoviment = clone($movimentacao->dtMoviment);
            $moviment191->dtVencto = $i === 1 ? (clone $primeiraDtVencto) :
                (clone $movimentacao->dtMoviment)->add(new \DateInterval('P' . ($i) . 'M'));
            $moviment191->dtVenctoEfetiva = clone($moviment191->dtVencto);

            $moviment191->tipoLancto = $movimentacao->tipoLancto;
            $moviment191->jsonData = $movimentacao->jsonData;
            parent::save($moviment191);
        }

        // $movimentacao->operadoraCartao = null; // remove
        parent::save($movimentacao);
        $this->getDoctrine()->commit();

        return $movimentacao;
    }


    /**
     * Tratamento para casos de movimentação em cadeia.
     * @param $movimentacao
     * @throws ViewException
     */
    public function delete($movimentacao)
    {
        /**
         * Para movimentações de uma cadeia vinculante, todas devem ser deletadas.
         */
        /** @var Movimentacao $movimentacao */
        if ($movimentacao->cadeia &&
            $movimentacao->cadeia->vinculante &&
            $movimentacao->cadeia->movimentacoes) {
            $movimentacoes = $movimentacao->cadeia->movimentacoes;
            foreach ($movimentacoes as $m) {
                parent::delete($m);
            }
        } else {
            parent::delete($movimentacao);
        }
    }


    /**
     * @required
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }


    public function getEntityClass()
    {
        return Movimentacao::class;
    }


    /**
     * @param Movimentacao $movimentacao
     * @throws ViewException
     */
    public function refindAll(Movimentacao $movimentacao): void
    {
        try {
            $em = $this->doctrine;

            if ($movimentacao->categoria && $movimentacao->categoria->getId()) {
                /** @var Categoria $categoria */
                $categoria = $em->find(Categoria::class, $movimentacao->categoria->getId());
                $movimentacao->categoria = $categoria;
            }
            if ($movimentacao->tipoLancto && $movimentacao->tipoLancto->getId()) {
                /** @var TipoLancto $tipoLancto */
                $tipoLancto = $em->find(TipoLancto::class, $movimentacao->tipoLancto->getId());
                $movimentacao->tipoLancto = $tipoLancto;
            }
            if ($movimentacao->carteira && $movimentacao->carteira->getId()) {
                /** @var Carteira $carteira */
                $carteira = $em->find(Carteira::class, $movimentacao->carteira->getId());
                $movimentacao->carteira = $carteira;
            }
            if ($movimentacao->carteiraDestino && $movimentacao->carteiraDestino->getId()) {
                /** @var Carteira $carteiraDestino */
                $carteiraDestino = $em->find(Carteira::class, $movimentacao->carteiraDestino->getId());
                $movimentacao->carteiraDestino = $carteiraDestino;
            }
            if ($movimentacao->centroCusto && $movimentacao->centroCusto->getId()) {
                /** @var CentroCusto $centroCusto */
                $centroCusto = $em->find(CentroCusto::class, $movimentacao->centroCusto->getId());
                $movimentacao->centroCusto = $centroCusto;
            }
            if ($movimentacao->modo && $movimentacao->modo->getId()) {
                /** @var Modo $modo */
                $modo = $em->find(Modo::class, $movimentacao->modo->getId());
                $movimentacao->modo = $modo;
            }
            if ($movimentacao->grupoItem && $movimentacao->grupoItem->getId()) {
                /** @var GrupoItem $grupoItem */
                $grupoItem = $em->find(GrupoItem::class, $movimentacao->grupoItem->getId());
                $movimentacao->grupoItem = $grupoItem;
            }
            if ($movimentacao->operadoraCartao && $movimentacao->operadoraCartao->getId()) {
                /** @var OperadoraCartao $operadoraCartao */
                $operadoraCartao = $em->find(OperadoraCartao::class, $movimentacao->operadoraCartao->getId());
                $movimentacao->operadoraCartao = $operadoraCartao;
            }
            if ($movimentacao->bandeiraCartao && $movimentacao->bandeiraCartao->getId()) {
                /** @var BandeiraCartao $bandeiraCartao */
                $bandeiraCartao = $em->find(BandeiraCartao::class, $movimentacao->bandeiraCartao->getId());
                $movimentacao->bandeiraCartao = $bandeiraCartao;
            }
            if ($movimentacao->cadeia && $movimentacao->cadeia->getId()) {
                /** @var Cadeia $cadeia */
                $cadeia = $em->find(Cadeia::class, $movimentacao->cadeia->getId());
                $movimentacao->cadeia = $cadeia;
            }
            if ($movimentacao->documentoBanco && $movimentacao->documentoBanco->getId()) {
                /** @var Banco $documentoBanco */
                $documentoBanco = $em->find(Banco::class, $movimentacao->documentoBanco->getId());
                $movimentacao->documentoBanco = $documentoBanco;
            }
            if ($movimentacao->chequeBanco && $movimentacao->chequeBanco->getId()) {
                /** @var Banco $chequeBanco */
                $chequeBanco = $em->find(Banco::class, $movimentacao->chequeBanco->getId());
                $movimentacao->chequeBanco = $chequeBanco;
            }
            if ($movimentacao->fatura && $movimentacao->fatura->getId()) {
                /** @var Fatura $fatura */
                $fatura = $em->find(Fatura::class, $movimentacao->fatura->getId());
                $movimentacao->fatura = $fatura;
            }
        } catch (\Exception $e) {
            $msg = ExceptionUtils::treatException($e);
            throw new ViewException('Erro ao realizar o refindAll (' . $msg . ')');
        }
    }

    public function beforeClone(/** @var Movimentacao $movimentacao */ $movimentacao)
    {
        $movimentacao->UUID = null;
    }

    /**
     *
     * @param Cadeia $cadeia
     * @throws ViewException
     */
    public function deleteCadeiaETodasAsMovimentacoes(Cadeia $cadeia): void
    {
        try {
            $this->doctrine->beginTransaction();
            $movs = $cadeia->movimentacoes;
            /** @var Movimentacao $mov */
            foreach ($movs as $mov) {
                $this->delete($mov);
            }
            $this->faturaEntityHandler->cadeiaEntityHandler->delete($cadeia);
            $this->doctrine->commit();
        } catch (\Throwable $e) {
            $this->doctrine->rollback();
            $err = $e->getMessage();
            if (isset($mov)) {
                $err .= ' (' . $mov->descricao . ')';
            }
            throw new ViewException($err);
        }
    }

    /**
     *
     */
    public function removerCadeiasComApenasUmaMovimentacao(): void
    {
        $rsm = new ResultSetMapping();
        $sql = 'select id, cadeia_id, count(cadeia_id) as qt from fin_movimentacao group by cadeia_id having qt < 2';
        $qry = $this->getDoctrine()->createNativeQuery($sql, $rsm);

        $rsm->addScalarResult('id', 'id');
        $rs = $qry->getResult();
        if ($rs) {
            foreach ($rs as $r) {
                /** @var Movimentacao $movimentacao */
                $movimentacao = $this->getDoctrine()->find(Movimentacao::class, $r['id']);
                if ($movimentacao->cadeia) {
                    $cadeia = $this->getDoctrine()->find(Cadeia::class, $movimentacao->cadeia);
                    $movimentacao->cadeia = null;
                    $this->getDoctrine()->remove($cadeia);
                }
            }
        }
        $this->getDoctrine()->flush();
    }

    /**
     * @param Movimentacao $movimentacao
     * @throws ViewException
     */
    private function saveParcelamento(Movimentacao $movimentacao)
    {
        try {
            $this->doctrine->beginTransaction();

            $dadosParcelamento = $movimentacao->jsonData['dadosParcelamento'];

            unset($movimentacao->jsonData['dadosParcelamento']);

            $cadeia = new Cadeia();
            $cadeia->vinculante = true;
            $cadeia->fechada = true;

            $movimentacao->parcelamento = true;
            $movimentacao->cadeia = $cadeia;
            $movimentacao->cadeiaQtde = count($dadosParcelamento);
            $movimentacao->cadeiaOrdem = 1;

            $cadeia->movimentacoes->add($movimentacao);

            parent::save($movimentacao, false);

            for ($i = 1; $i < count($dadosParcelamento); $i++) {
                /** @var Movimentacao $parcela */
                $parcela = $this->cloneEntityId($movimentacao);
                $parcela->parcelamento = true;
                $parcela->cadeia = $cadeia;
                $parcela->cadeiaQtde = count($dadosParcelamento);
                $parcela->cadeiaOrdem = $i + 1;
                $parcela->dtVencto = DateTimeUtils::parseDateStr($dadosParcelamento[$i]['dtVencto']);
                $parcela->dtVenctoEfetiva = DateTimeUtils::parseDateStr($dadosParcelamento[$i]['dtVenctoEfetiva']);
                $parcela->valor = $dadosParcelamento[$i]['valor'];
                $parcela->documentoNum = $dadosParcelamento[$i]['documentoNum'] ?? null;
                $parcela->chequeNumCheque = $dadosParcelamento[$i]['chequeNumCheque'] ?? null;
                $cadeia->movimentacoes->add($parcela);

                parent::save($parcela, false);
            }
            $cadeia = $this->faturaEntityHandler->cadeiaEntityHandler->save($cadeia);
            $this->doctrine->commit();
        } catch (ViewException $e) {
            if ($this->doctrine->getConnection()->isTransactionActive()) {
                try {
                    $this->doctrine->rollback();
                } catch (\Exception $e) {
                    throw new ViewException('Erro no rollback - ');
                }
            }
            throw new ViewException('Erro ao salvar o parcelamento', 0, $e);
        }


    }


}
