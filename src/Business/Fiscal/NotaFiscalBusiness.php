<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Entity\Base\Municipio;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\Config\AppConfigEntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Repository\Base\MunicipioRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\CentroCusto;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Fatura;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\TipoLancto;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\FinalidadeNF;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\IndicadorFormaPagto;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NCM;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalCartaCorrecao;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalHistorico;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalVenda;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\TipoNotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaItem;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\MovimentacaoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalHistoricoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalItemEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalVendaEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NCMRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\ResultSetMapping;
use Psr\Log\LoggerInterface;

/**
 *
 * @package App\Business\Fiscal
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscalBusiness
{

    private Connection $conn;

    private LoggerInterface $logger;

    private SpedNFeBusiness $spedNFeBusiness;

    private AppConfigEntityHandler $appConfigEntityHandler;

    private NotaFiscalEntityHandler $notaFiscalEntityHandler;

    private NotaFiscalItemEntityHandler $notaFiscalItemEntityHandler;

    private NotaFiscalVendaEntityHandler $notaFiscalVendaEntityHandler;

    private NotaFiscalHistoricoEntityHandler $notaFiscalHistoricoEntityHandler;

    private MovimentacaoEntityHandler $movimentacaoEntityHandler;

    private NFeUtils $nfeUtils;

    private SyslogBusiness $syslog;

    /**
     * Não podemos usar o doctrine->getRepository porque ele não injeta as depêndencias que estão com @ required lá
     * @var NotaFiscalRepository
     */
    private NotaFiscalRepository $repoNotaFiscal;

    /**
     * NotaFiscalBusiness constructor.
     * @param Connection $conn
     * @param LoggerInterface $logger
     * @param SpedNFeBusiness $spedNFeBusiness
     * @param AppConfigEntityHandler $appConfigEntityHandler
     * @param NotaFiscalEntityHandler $notaFiscalEntityHandler
     * @param NotaFiscalItemEntityHandler $notaFiscalItemEntityHandler
     * @param NotaFiscalVendaEntityHandler $notaFiscalVendaEntityHandler
     * @param NotaFiscalHistoricoEntityHandler $notaFiscalHistoricoEntityHandler
     * @param MovimentacaoEntityHandler $movimentacaoEntityHandler
     * @param NFeUtils $nfeUtils
     * @param SyslogBusiness $syslog
     * @param NotaFiscalRepository $repoNotaFiscal
     */
    public function __construct(Connection $conn,
                                LoggerInterface $logger,
                                SpedNFeBusiness $spedNFeBusiness,
                                AppConfigEntityHandler $appConfigEntityHandler,
                                NotaFiscalEntityHandler $notaFiscalEntityHandler,
                                NotaFiscalItemEntityHandler $notaFiscalItemEntityHandler,
                                NotaFiscalVendaEntityHandler $notaFiscalVendaEntityHandler,
                                NotaFiscalHistoricoEntityHandler $notaFiscalHistoricoEntityHandler,
                                MovimentacaoEntityHandler $movimentacaoEntityHandler,
                                NFeUtils $nfeUtils,
                                SyslogBusiness $syslog,
                                NotaFiscalRepository $repoNotaFiscal)
    {
        $this->conn = $conn;
        $this->logger = $logger;
        $this->spedNFeBusiness = $spedNFeBusiness;
        $this->appConfigEntityHandler = $appConfigEntityHandler;
        $this->notaFiscalEntityHandler = $notaFiscalEntityHandler;
        $this->notaFiscalItemEntityHandler = $notaFiscalItemEntityHandler;
        $this->notaFiscalVendaEntityHandler = $notaFiscalVendaEntityHandler;
        $this->notaFiscalHistoricoEntityHandler = $notaFiscalHistoricoEntityHandler;
        $this->movimentacaoEntityHandler = $movimentacaoEntityHandler;
        $this->nfeUtils = $nfeUtils;
        $this->syslog = $syslog;
        $this->repoNotaFiscal = $repoNotaFiscal;
    }


    /**
     * Verifica se está acessando o arquivo controle.txt para evitar trabalhar com diretório desmontado.
     * @return bool
     */
    public function checkAcessoPVs(): bool
    {
        $dir = $_SERVER['PASTAARQUIVOSEKTFISCAL'];
        $files = scandir($dir, SCANDIR_SORT_NONE);
        return in_array('controle.txt', $files, true) ? true : false;
    }

    /**
     * Transforma um ven_venda em um fis_nf.
     *
     * @param Venda $venda
     * @param NotaFiscal $notaFiscal
     * @param bool $alterouTipo
     * @return null|NotaFiscal
     * @throws ViewException
     */
    public function saveNotaFiscalVenda(Venda $venda, NotaFiscal $notaFiscal, bool $alterouTipo): ?NotaFiscal
    {
        try {
            $conn = $this->notaFiscalEntityHandler->getDoctrine()->getConnection();
            $jaExiste = $conn->fetchAll('SELECT * FROM fis_nf_venda WHERE venda_id = :vendaId', ['vendaId' => $venda->getId()]);

            $rNcmPadrao = $conn->fetchAll('SELECT valor FROM cfg_app_config WHERE chave = \'ncm_padrao\'');
            $ncmPadrao = $rNcmPadrao[0]['valor'] ?? null;

            if ($jaExiste) {
                /** @var NotaFiscalRepository $repoNotaFiscal */
                $repoNotaFiscal = $this->notaFiscalEntityHandler->getDoctrine()->getRepository(NotaFiscal::class);
                /** @var NotaFiscal $notaFiscal */
                $notaFiscal = $repoNotaFiscal->find($jaExiste[0]['nota_fiscal_id']);
                $novaNota = false;
            } else {
                $novaNota = true;
            }

            if ($notaFiscal->getId()) {
                /** @var Connection $conn */
                $conn = $this->notaFiscalEntityHandler->getDoctrine()->getConnection();
                $conn->delete('fis_nf_item', ['nota_fiscal_id' => $notaFiscal->getId()]);
            }
            $notaFiscal->deleteAllItens();


            $this->notaFiscalEntityHandler->getDoctrine()->beginTransaction();

            $notaFiscal->setEntradaSaida('S');

            $nfeConfigs = $this->nfeUtils->getNFeConfigsEmUso();

            $notaFiscal->setDocumentoEmitente($nfeConfigs['cnpj']);
            $notaFiscal->setXNomeEmitente($nfeConfigs['razaosocial']);
            $notaFiscal->setInscricaoEstadualEmitente($nfeConfigs['ie']);

            $notaFiscal->setNaturezaOperacao('VENDA');

            $dtEmissao = new \DateTime();
            $dtEmissao->modify(' - 4 minutes');
            $notaFiscal->setDtEmissao($dtEmissao);
            $notaFiscal->setDtSaiEnt($dtEmissao);

            $notaFiscal->setFinalidadeNf(FinalidadeNF::NORMAL['key']);

            if ($alterouTipo) {
                $notaFiscal->setDtEmissao(null);
                $notaFiscal->setNumero(null);
                $notaFiscal->setCnf(null);
                $notaFiscal->setChaveAcesso(null);
            }

            // Aqui somente coisas que fazem sentido serem alteradas depois de já ter sido (provavelmente) tentado o faturamento da Notafiscal.
            $notaFiscal->setTranspModalidadeFrete('SEM_FRETE');

            $notaFiscal->setIndicadorFormaPagto(
                $venda->planoPagto->codigo === '1.00' ? IndicadorFormaPagto::VISTA['codigo'] : IndicadorFormaPagto::PRAZO['codigo']);

            $notaFiscal = $this->notaFiscalEntityHandler->deleteAllItens($notaFiscal);
            $this->notaFiscalEntityHandler->getDoctrine()->flush();

            // Atenção, aqui tem que verificar a questão do arredondamento
            if ($venda->subtotal > 0.0) {
                $fatorDesconto = 1 - round(bcdiv($venda->getValorTotal(), $venda->subtotal, 4), 2);
            } else {
                $fatorDesconto = 1;
            }

            $somaDescontosItens = 0.0;
            $ordem = 1;
            /** @var VendaItem $vendaItem */
            foreach ($venda->itens as $vendaItem) {

                $nfItem = new NotaFiscalItem();
                $nfItem->setNotaFiscal($notaFiscal);

                $ncm = $vendaItem->jsonData['ncm'] ?? $vendaItem->produto->jsonData['ncm'] ?? $ncmPadrao ?? null;
                if (!$ncm) {
                    throw new ViewException('Item da Venda sem NCM');
                }
                $nfItem->setNcm($ncm);

                $nfItem->setOrdem($ordem++);

                $nfItem->setQtde($vendaItem->qtde);
                $nfItem->setValorUnit($vendaItem->precoVenda);
                $valorTotalItem = bcmul($vendaItem->qtde, $vendaItem->precoVenda, 2);
                $nfItem->setValorTotal($valorTotalItem);

                $vDesconto = round(bcmul($valorTotalItem, $fatorDesconto, 4), 2);
                $nfItem->setValorDesconto($vDesconto);

                // Somando aqui pra verificar depois se o total dos descontos dos itens bate com o desconto global da nota.
                $somaDescontosItens += $vDesconto;

                $nfItem->setSubTotal($valorTotalItem);

                $nfItem->setIcmsAliquota(0.0);
                $nfItem->setCfop('5102');
                if ($vendaItem->produto->jsonData['unidade_produto'] ?? null) {
                    $nfItem->setUnidade($vendaItem->produto->jsonData['unidade_produto']);
                } else {
                    $nfItem->setUnidade('PC');
                }

                if ($vendaItem->produto !== null) {
                    $this->notaFiscalEntityHandler->getDoctrine()->getRepository(Produto::class)->findOneBy(['id' => $vendaItem->produto->getId()]);
                    $nfItem->setCodigo($vendaItem->produto->getId());
                    $nfItem->setDescricao(trim($vendaItem->produto->nome));
                } else {
                    $nfItem->setCodigo($vendaItem->jsonData['produto']['reduzido'] ?? 00000);
                    $nfItem->setDescricao(trim($vendaItem->jsonData['produto']['descricao']) ?? 'PRODUTO 00000');
                }

                $this->notaFiscalEntityHandler->handleSavingEntityId($nfItem);
                $notaFiscal->addItem($nfItem);
            }

            $this->notaFiscalEntityHandler->calcularTotais($notaFiscal);
            $totalDescontos = bcsub($notaFiscal->getSubTotal(), $notaFiscal->getValorTotal(), 2);

            if ((float)bcsub(abs($totalDescontos), abs($somaDescontosItens), 2) !== 0.0) {
                $diferenca = $totalDescontos - $somaDescontosItens;
                $notaFiscal->getItens()
                    ->get(0)
                    ->setValorDesconto($notaFiscal->getItens()
                            ->get(0)
                            ->getValorDesconto() + $diferenca);
                $notaFiscal->getItens()
                    ->get(0)
                    ->calculaTotais();
            }

            /** @var NotaFiscal $notaFiscal */
            $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);
            $this->notaFiscalEntityHandler->getDoctrine()->flush();

            if ($novaNota) {
                $notaFiscalVenda = new NotaFiscalVenda();
                $notaFiscalVenda->setNotaFiscal($notaFiscal);
                $notaFiscalVenda->setVenda($venda);
                $this->notaFiscalVendaEntityHandler->save($notaFiscalVenda);
            }

            $this->notaFiscalEntityHandler->getDoctrine()->commit();
            return $notaFiscal;
        } catch (\Exception $e) {
            $this->notaFiscalEntityHandler->getDoctrine()->rollback();
            if ($e instanceof ViewException) {
                throw $e;
            }
            $erro = 'Erro ao gerar registro da Nota Fiscal';
            throw new \RuntimeException($erro, null, $e);
        }
    }

    /**
     * Lida com os campos que são gerados programaticamente.
     *
     * @param $notaFiscal
     * @return bool
     * @throws ViewException
     */
    public function handleIdeFields(NotaFiscal $notaFiscal): bool
    {
        try {
            $mudou = false;
            if (!$notaFiscal->getUuid()) {
                $notaFiscal->setUuid(md5(uniqid(mt_rand(), true)));
                $mudou = true;
            }
            if (!$notaFiscal->getCnf()) {
                $cNF = random_int(10000000, 99999999);
                $notaFiscal->setCnf($cNF);
                $mudou = true;
            }
            // Rejeição 539: Duplicidade de NF-e, com diferença na Chave de Acesso
            // Rejeição 266: 266 - SERIE UTILIZADA FORA DA FAIXA PERMITIDA NO WEB SERVICE (0-889).
            if (!$notaFiscal->getNumero() || in_array($notaFiscal->getCStat(), [539, 266], true)) {
                $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($notaFiscal->getDocumentoEmitente());

                $ambiente = $nfeConfigs['tpAmb'] === 1 ? 'PROD' : 'HOM';
                $notaFiscal->setAmbiente($ambiente);

                if (!$notaFiscal->getTipoNotaFiscal()) {
                    throw new \Exception('Impossível gerar número sem saber o tipo da nota fiscal.');
                }
                $chaveSerie = 'serie_' . $notaFiscal->getTipoNotaFiscal() . '_' . $ambiente;
                $serie = $nfeConfigs[$chaveSerie];
                if (!$serie) {
                    throw new ViewException('Série não encontrada para ' . $chaveSerie);
                }
                $notaFiscal->setSerie($serie);

                /** @var NotaFiscalRepository $repoNotaFiscal */
                $nnf = $this->findProxNumFiscal($notaFiscal->getDocumentoEmitente(), $ambiente, $notaFiscal->getSerie(), $notaFiscal->getTipoNotaFiscal());
                $notaFiscal->setNumero($nnf);
                $mudou = true;
            }
            if (!$notaFiscal->getDtEmissao()) {
                $notaFiscal->setDtEmissao(new \DateTime());
                $mudou = true;
            }
            if ($mudou || !$notaFiscal->getChaveAcesso() || !preg_match('/[0-9]{44}/', $notaFiscal->getChaveAcesso())) {
                $notaFiscal->setChaveAcesso($this->buildChaveAcesso($notaFiscal));
                $mudou = true;
            }
            if ($mudou) {
                $this->notaFiscalEntityHandler->save($notaFiscal);
            }
            return $mudou;
        } catch (\Throwable $e) {
            $this->logger->error('handleIdeFields');
            $this->logger->error($e->getMessage());
            throw new ViewException('Erro ao gerar campos ide');
        }
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return string
     * @throws ViewException
     */
    public function buildChaveAcesso(NotaFiscal $notaFiscal)
    {
        $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($notaFiscal->getDocumentoEmitente());
        $cUF = '41';

        $cnpj = $nfeConfigs['cnpj'];
        $ano = $notaFiscal->getDtEmissao()->format('y');
        $mes = $notaFiscal->getDtEmissao()->format('m');
        $mod = TipoNotaFiscal::get($notaFiscal->getTipoNotaFiscal())['codigo'];
        $serie = $notaFiscal->getSerie();
        if (strlen($serie) > 3) {
            throw new ViewException('Série deve ter no máximo 3 dígitos');
        }
        $nNF = $notaFiscal->getNumero();
        $cNF = $notaFiscal->getCnf();

        // Campo tpEmis
        // 1-Emissão Normal
        // 2-Contingência em Formulário de Segurança
        // 3-Contingência SCAN (desativado)
        // 4-Contingência EPEC
        // 5-Contingência em Formulário de Segurança FS-DA
        // 6-Contingência SVC-AN
        // 7-Contingência SVC-RS
        $tpEmis = 1;

        $chaveAcesso = NFeKeys::build($cUF, $ano, $mes, $cnpj, $mod, $serie, $nNF, $tpEmis, $cNF);
        return $chaveAcesso;
    }

    /**
     * Salvar uma notaFiscal normal.
     *
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal|null
     * @throws ViewException
     */
    public function saveNotaFiscal(NotaFiscal $notaFiscal): ?NotaFiscal
    {
        try {
            if (!$notaFiscal->getTipoNotaFiscal()) {
                throw new ViewException('Tipo da Nota não informado');
            }
            $this->notaFiscalEntityHandler->getDoctrine()->beginTransaction();

            $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($notaFiscal->getDocumentoEmitente());

            $notaFiscal->setXNomeEmitente($nfeConfigs['razaosocial']);
            $notaFiscal->setInscricaoEstadualEmitente($nfeConfigs['ie']);

            if (!$notaFiscal->getUuid()) {
                $notaFiscal->setUuid(md5(uniqid(mt_rand(), true)));
            }

            if (!$notaFiscal->getSerie()) {
                $ambiente = $nfeConfigs['tpAmb'] === 1 ? 'PROD' : 'HOM';
                $notaFiscal->setSerie($notaFiscal->getTipoNotaFiscal() === 'NFE' ? $nfeConfigs['serie_NFE_' . $ambiente] : $nfeConfigs['serie_NFCE_' . $ambiente]);
            }

            if (!$notaFiscal->getCnf()) {
                $cNF = random_int(10000000, 99999999);
                $notaFiscal->setCnf($cNF);
            }

            $this->notaFiscalEntityHandler->calcularTotais($notaFiscal);
            $this->notaFiscalEntityHandler->save($notaFiscal);
            $this->notaFiscalEntityHandler->getDoctrine()->commit();
            return $notaFiscal;
        } catch (\Exception $e) {
            $this->notaFiscalEntityHandler->getDoctrine()->rollback();
            $erro = 'Erro ao salvar Nota Fiscal';
            if ($e instanceof ViewException) {
                $erro .= ' (' . $e->getMessage() . ')';
            }
            throw new ViewException($erro, null, $e);
        }
    }

    /**
     * Corrige os NCMs. Na verdade troca para um NCM genérico nos casos onde o NCM informado não exista na base.
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws \Exception
     */
    public function corrigirNCMs(NotaFiscal $notaFiscal): NotaFiscal
    {
        $this->notaFiscalEntityHandler->getDoctrine()->refresh($notaFiscal);
        if ($notaFiscal->getItens()) {
            foreach ($notaFiscal->getItens() as $item) {
                /** @var NCMRepository $repoNCM */
                $repoNCM = $this->notaFiscalEntityHandler->getDoctrine()->getRepository(NCM::class);
                $existe = $repoNCM->findByNCM($item->getNcm());
                if (!$existe) {
                    $item->setNcm('62179000');
                }
            }
        }
        $this->notaFiscalEntityHandler->getDoctrine()->flush();
        return $notaFiscal;
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws \Exception
     */
    public function faturarNFe(NotaFiscal $notaFiscal): NotaFiscal
    {
        // Verifica algumas regras antes de mandar faturar na receita.
        $this->checkNotaFiscal($notaFiscal);

        $this->addHistorico($notaFiscal, -1, 'INICIANDO FATURAMENTO');
        if ($this->permiteFaturamento($notaFiscal)) {

            try {
                if ($notaFiscal->getNRec()) {
                    $this->spedNFeBusiness->consultaRecibo($notaFiscal);
                    if ($notaFiscal->getCStat() === 502) {
                        $notaFiscal->setChaveAcesso(null); // será regerada no handleIdeFields()
                    }
                }
                $this->handleIdeFields($notaFiscal);
                $notaFiscal = $this->spedNFeBusiness->gerarXML($notaFiscal);
                $notaFiscal = $this->spedNFeBusiness->enviaNFe($notaFiscal);
                if ($notaFiscal) {
                    $this->addHistorico($notaFiscal, $notaFiscal->getCStat() ?: -1, $notaFiscal->getXMotivo(), 'FATURAMENTO PROCESSADO');
                    // $this->imprimir($notaFiscal);
                } else {
                    $this->addHistorico($notaFiscal, -2, 'PROBLEMA AO FATURAR');
                }
            } catch (ViewException $e) {
                $this->addHistorico($notaFiscal, -2, $e->getMessage());
            }

        } else {
            $this->addHistorico($notaFiscal, 0, 'NOTA FISCAL NÃO FATURÁVEL. STATUS = [' . $notaFiscal->getCStat() . ']');
        }

        return $notaFiscal;
    }

    /**
     *
     * @param NotaFiscal $notaFiscal
     * @throws \Exception
     */
    public function checkNotaFiscal(NotaFiscal $notaFiscal): void
    {
        if (!$notaFiscal) {
            throw new \RuntimeException('Nota Fiscal null');
        }
        if ($notaFiscal->getCidadeDestinatario()) {

            /** @var MunicipioRepository $repoMunicipio */
            $repoMunicipio = $this->notaFiscalEntityHandler->getDoctrine()->getRepository(Municipio::class);

            /** @var Municipio $r */
            $r = $repoMunicipio->findOneByFiltersSimpl([
                ['municipioNome', 'EQ', $notaFiscal->getCidadeDestinatario()],
                ['ufSigla', 'EQ', $notaFiscal->getEstadoDestinatario()]
            ]);


            if (!$r || strtoupper(StringUtils::removerAcentos($r->getMunicipioNome())) !== strtoupper(StringUtils::removerAcentos($notaFiscal->getCidadeDestinatario()))) {
                throw new ViewException('Município inválido: [' . $notaFiscal->getCidadeDestinatario() . '-' . $notaFiscal->getEstadoDestinatario() . ']');
            }
        }

        if ($notaFiscal->getDtEmissao() > $notaFiscal->getDtSaiEnt()) {
            throw new ViewException('Dt Emissão maior que Dt Saída/Entrada. Não é possível faturar.');
        }

    }

    /**
     * @param NotaFiscal $notaFiscal
     * @param $codigoStatus
     * @param $descricao
     * @param null $obs
     * @throws ViewException
     */
    public function addHistorico(NotaFiscal $notaFiscal, $codigoStatus, $descricao, $obs = null): void
    {
        $historico = new NotaFiscalHistorico();
        $dtHistorico = new \DateTime();
        $historico->setDtHistorico($dtHistorico);
        $historico->setCodigoStatus($codigoStatus);
        $historico->setDescricao($descricao ? $descricao : ' ');
        $historico->setObs($obs);
        $historico->setNotaFiscal($notaFiscal);
        $this->notaFiscalHistoricoEntityHandler->save($historico);
    }

    /**
     * Só exibe o botão faturar se tiver nestas condições.
     * Lembrando que o botão "Faturar" serve tanto para faturar a primeira vez, como para tentar faturar novamente nos casos de erros.
     *
     * @param NotaFiscal $notaFiscal
     * @return bool
     */
    public function permiteFaturamento(NotaFiscal $notaFiscal): bool
    {
        if ($notaFiscal && $notaFiscal->getId() && in_array($notaFiscal->getCStat(), [-100, 100, 101, 204, 135], false)) {
            return false;
        }
        if ($notaFiscal && !$notaFiscal->getId()) {
            return false;
        }
        return true;

    }

    /**
     * Só exibe o botão faturar se tiver nestas condições.
     * Lembrando que o botão "Faturar" serve tanto para faturar a primeira vez, como para tentar faturar novamente nos casos de erros.
     *
     * @param NotaFiscal $notaFiscal
     * @return bool
     */
    public function permiteSalvar(NotaFiscal $notaFiscal)
    {
        if (!$notaFiscal->getId() || $this->permiteFaturamento($notaFiscal)) {
            return true;
        }
        return false;

    }

    /**
     * Por enquanto o 'cancelar' segue a mesma regra do 'reimprimir'.
     *
     * @param NotaFiscal $notaFiscal
     * @return bool
     */
    public function permiteCancelamento(NotaFiscal $notaFiscal): ?bool
    {
        return (int)$notaFiscal->getCStat() === 100;
    }

    /**
     * Verifica se é possível reimprimir.
     *
     * @param NotaFiscal $notaFiscal
     * @return boolean
     */
    public function permiteReimpressao(NotaFiscal $notaFiscal)
    {
        if ($notaFiscal->getId()) {
            if ($notaFiscal->getCStat() == 100 || $notaFiscal->getCStat() == 204 || $notaFiscal->getCStat() == 135) {
                return true;
            }
            // else
            if ($notaFiscal->getCStat() == 0 && strpos($notaFiscal->getXMotivo(), 'DUPLICIDADE DE NF') !== FALSE) {
                return true;
            }

        }
        return false;
    }

    /**
     * Verifica se é possível reimprimir o cancelamento.
     *
     * @param NotaFiscal $notaFiscal
     * @return boolean
     */
    public function permiteReimpressaoCancelamento(NotaFiscal $notaFiscal)
    {
        if ($notaFiscal->getId()) {
            if ($notaFiscal->getCStat() == 101) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se é possível enviar carta de correção.
     *
     * @param NotaFiscal $notaFiscal
     * @return boolean
     */
    public function permiteCartaCorrecao(NotaFiscal $notaFiscal)
    {
        if ($notaFiscal->getId()) {
            if ($notaFiscal->getCStat() == 100) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param NotaFiscal $notaFiscal
     */
    public function imprimir(NotaFiscal $notaFiscal)
    {
        $this->spedNFeBusiness->imprimir($notaFiscal);
    }

    /**
     * @param NotaFiscal $notaFiscal
     */
    public function imprimirCancelamento(NotaFiscal $notaFiscal)
    {
        $this->spedNFeBusiness->imprimirCancelamento($notaFiscal);
    }

    /**
     * @param NotaFiscalCartaCorrecao $cartaCorrecao
     */
    public function imprimirCartaCorrecao(NotaFiscalCartaCorrecao $cartaCorrecao)
    {
        $this->spedNFeBusiness->imprimirCartaCorrecao($cartaCorrecao);
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal|\CrosierSource\CrosierLibBaseBundle\Entity\EntityId|object
     * @throws ViewException
     */
    public function cancelar(NotaFiscal $notaFiscal)
    {
        $this->addHistorico($notaFiscal, -1, 'INICIANDO CANCELAMENTO');
        $notaFiscal = $this->checkChaveAcesso($notaFiscal);
        try {
            $notaFiscalR = $this->spedNFeBusiness->cancelar($notaFiscal);
            if ($notaFiscalR) {
                $notaFiscal = $notaFiscalR;
                $this->addHistorico($notaFiscal, $notaFiscal->getCStat() ?: -1, $notaFiscal->getXMotivo(), 'CANCELAMENTO PROCESSADO');
                $notaFiscal = $this->consultarStatus($notaFiscal);
                $this->spedNFeBusiness->imprimirCancelamento($notaFiscal);
            } else {
                $this->addHistorico($notaFiscal, -2, 'PROBLEMA AO CANCELAR');
            }
        } catch (\Exception | ViewException $e) {
            $this->addHistorico($notaFiscal, -2, 'PROBLEMA AO CANCELAR: [' . $e->getMessage() . ']');
            if ($e instanceof ViewException) {
                $this->addHistorico($notaFiscal, -2, $e->getMessage());
            }
        }
        return $notaFiscal;
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal|\CrosierSource\CrosierLibBaseBundle\Entity\EntityId|object
     * @throws ViewException
     */
    public function checkChaveAcesso(NotaFiscal $notaFiscal)
    {
        if (!$notaFiscal->getChaveAcesso()) {
            $notaFiscal->setChaveAcesso($this->buildChaveAcesso($notaFiscal));

            $notaFiscal = $this->notaFiscalEntityHandler->save($notaFiscal);
            $this->notaFiscalEntityHandler->getDoctrine()->flush();
        }
        return $notaFiscal;
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @return NotaFiscal
     * @throws ViewException
     */
    public function consultarStatus(NotaFiscal $notaFiscal): NotaFiscal
    {
        $this->addHistorico($notaFiscal, -1, 'INICIANDO CONSULTA DE STATUS');
        try {
            $notaFiscal = $this->spedNFeBusiness->consultarStatus($notaFiscal);
            if ($notaFiscal) {
                $this->addHistorico($notaFiscal, $notaFiscal->getCStat() ?: -1, $notaFiscal->getXMotivo(), 'CONSULTA DE STATUS PROCESSADA');
            } else {
                $this->addHistorico($notaFiscal, -2, 'PROBLEMA AO CONSULTAR STATUS');
            }
        } catch (\Exception $e) {
            $this->addHistorico($notaFiscal, -2, 'PROBLEMA AO CONSULTAR STATUS: [' . $e->getMessage() . ']');
        }
        return $notaFiscal;
    }

    /**
     * @param NotaFiscalCartaCorrecao $cartaCorrecao
     * @return NotaFiscal|NotaFiscalCartaCorrecao
     * @throws ViewException
     */
    public function cartaCorrecao(NotaFiscalCartaCorrecao $cartaCorrecao)
    {
        $this->addHistorico($cartaCorrecao->getNotaFiscal(), -1, 'INICIANDO ENVIO DA CARTA DE CORREÇÃO');
        try {
            $cartaCorrecao = $this->spedNFeBusiness->cartaCorrecao($cartaCorrecao);
            if ($cartaCorrecao) {
                $this->addHistorico(
                    $cartaCorrecao->getNotaFiscal(),
                    $cartaCorrecao->getNotaFiscal()->getCStat(),
                    $cartaCorrecao->getNotaFiscal()->getXMotivo(),
                    'ENVIO DA CARTA DE CORREÇÃO PROCESSADO');
                $this->consultarStatus($cartaCorrecao->getNotaFiscal());
                // $this->spedNFeBusiness->imprimirCartaCorrecao($cartaCorrecao);
            } else {
                $this->addHistorico($cartaCorrecao->getNotaFiscal(), -2, 'PROBLEMA AO ENVIAR CARTA DE CORREÇÃO');
            }
        } catch (\Exception $e) {
            $this->addHistorico($cartaCorrecao->getNotaFiscal(), -2, 'PROBLEMA AO ENVIAR CARTA DE CORREÇÃO: [' . $e->getMessage() . ']');
        }
        return $cartaCorrecao;
    }

    /**
     * @param string $cnpj
     * @param string $uf
     * @return mixed
     * @throws ViewException
     */
    public function consultarCNPJ(string $cnpj, string $uf)
    {
        $r = [];
        $infCons = $this->spedNFeBusiness->consultarCNPJ($cnpj, $uf);
        if ($infCons->cStat->__toString() === '259') {
            $r['xMotivo'] = $infCons->xMotivo->__toString();
        } else {
            $r['dados'] = [
                'CNPJ' => $infCons->infCad->CNPJ->__toString(),
                'IE' => $infCons->infCad->IE->__toString(),
                'razaoSocial' => $infCons->infCad->xNome->__toString(),
                'CNAE' => $infCons->infCad->CNAE->__toString(),
                'logradouro' => $infCons->infCad->ender->xLgr->__toString(),
                'numero' => $infCons->infCad->ender->nro->__toString(),
                'complemento' => $infCons->infCad->ender->xCpl->__toString(),
                'bairro' => $infCons->infCad->ender->xBairro->__toString(),
                'cidade' => $infCons->infCad->ender->xMun->__toString(),
                'UF' => $infCons->infCad->UF->__toString(),
                'CEP' => $infCons->infCad->ender->CEP->__toString(),
            ];
        }
        return $r;

    }


    /**
     * @param $mesano
     * @return bool|string
     * @throws \Exception
     */
    public function criarZip($mesano)
    {
        $mesano = str_replace(' - ', '', $mesano);
        $zip = new \ZipArchive();

        $pastaUnimake = $_SERVER['FISCAL_UNIMAKE_PASTAROOT'];
        $pastaXMLs = $pastaUnimake . '/enviado/Autorizados/' . $mesano;
        $pastaF = $_SERVER['PASTA_F'];

        $pastaNFEs = $pastaF . '/NOTAS FISCAIS/NFE/' . $mesano;
        $pastaNFCEs = $pastaF . '/NOTAS FISCAIS/NFCE/' . $mesano;
        $pastaCARTACORRs = $pastaF . '/NOTAS FISCAIS/CARTACORR/' . $mesano;

        $zipname = $pastaUnimake . '/backup/' . $mesano . '.zip';

        if ($zip->open($zipname, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception("cannot open <$zipname>");
        }

        $this->criarZipDir($zip, $pastaXMLs, 'xmls');
        $this->criarZipDir($zip, $pastaCARTACORRs, 'cartacorr');
        $this->criarZipDir($zip, $pastaNFCEs, 'nfce');
        $this->criarZipDir($zip, $pastaNFEs, 'nfe');

        // Zip archive will be created only after closing object
        $zip->close();
        return file_get_contents($zipname);

    }

    /**
     * @param \ZipArchive $zip
     * @param $pasta
     * @param $nomePasta
     */
    private function criarZipDir(\ZipArchive $zip, $pasta, $nomePasta)
    {
        $xmls = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pasta), \RecursiveIteratorIterator::LEAVES_ONLY);
        $zip->addEmptyDir($nomePasta);
        foreach ($xmls as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real && relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($pasta) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $nomePasta . '/' . $relativePath);
            }
        }
    }

    /**
     * @param NotaFiscal $notaFiscal
     * @param NotaFiscalItem $notaFiscalItem
     * @throws ViewException
     */
    public function colarItem(NotaFiscal $notaFiscal, NotaFiscalItem $notaFiscalItem)
    {
        /** @var NotaFiscalItem $novoItem */
        $novoItem = clone $notaFiscalItem;
        $novoItem->setId(null);
        $novoItem->setNotaFiscal($notaFiscal);
        $novoItem->setCodigo('?????');
        $novoItem->setOrdem(null);
        $this->notaFiscalItemEntityHandler->save($novoItem);
    }


    /**
     *
     * @return array obtido a partir das cfg_app_config de nfeConfigs_%
     */
    public function getEmitentes()
    {
        $nfeConfigs = $this->conn->fetchAll('SELECT * FROM cfg_app_config WHERE chave LIKE \'nfeConfigs\\_%\'');
        $emitentes = [];
        foreach ($nfeConfigs as $nfeConfig) {
            $dados = json_decode($nfeConfig['valor'], true);
            $emitentes[] = [
                'cnpj' => $dados['cnpj'],
                'razaosocial' => $dados['razaosocial'],
                'ie' => $dados['ie'],
                'logradouro' => $dados['enderEmit_xLgr'],
                'numero' => $dados['enderEmit_nro'],
                'bairro' => $dados['enderEmit_xBairro'],
                'cep' => $dados['enderEmit_cep'],
                'cidade' => $dados['enderEmit_xCidade'] ?? '',
                'estado' => $dados['siglaUF'],
                'fone1' => $dados['fone1'] ?? '',
            ];
        }
        return $emitentes;
    }

    /**
     * @param string $cnpj
     * @return bool
     */
    public function isCnpjEmitente(string $cnpj): bool
    {
        $emitentes = $this->getEmitentes();
        foreach ($emitentes as $emitente) {
            if ($emitente['cnpj'] === $cnpj) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $cnpj
     * @return array
     * @throws ViewException
     */
    public function getEmitenteFromNFeConfigsByCNPJ(string $cnpj): array
    {
        $emitentes = $this->getEmitentes();
        foreach ($emitentes as $emitente) {
            if ($emitente['cnpj'] === $cnpj) {
                return $emitente;
            }
        }
        throw new ViewException('CNPJ não encontrado nos emitentes');
    }


    /**
     * @param Venda $venda
     * @return null|NotaFiscalVenda
     * @throws ViewException
     */
    public function findNotaFiscalByVenda(Venda $venda): ?NotaFiscal
    {
        $nfeConfigs = $this->nfeUtils->getNFeConfigsEmUso();

        $ambiente = $nfeConfigs['tpAmb'] === 1 ? 'PROD' : 'HOM';

        $sql = 'SELECT nf.id FROM fis_nf_venda nfv, fis_nf nf WHERE nf.id = nfv.nota_fiscal_id AND nfv.venda_id = :venda_id AND nf.ambiente = :ambiente';

        $results = $this->conn->fetchAll($sql,
            [
                'venda_id' => $venda->getId(),
                'ambiente' => $ambiente
            ]);

        if (!$results) {
            return null;
        }

        if (count($results) > 1) {
            throw new \LogicException('Mais de uma Nota Fiscal encontrada para [' . $venda->getId() . ']');
        }

        /** @var NotaFiscalRepository $repoNotaFiscal */
        $repoNotaFiscal = $this->notaFiscalEntityHandler->getDoctrine()->getRepository(NotaFiscal::class);
        /** @var NotaFiscal $notaFiscal */
        $notaFiscal = $repoNotaFiscal->find($results[0]['id']);
        return $notaFiscal;
    }


    /**
     * @param string $ambiente
     * @param string $serie
     * @param string $tipoNotaFiscal
     * @return int
     */
    public function findProxNumFiscal(string $documentoEmitente, string $ambiente, string $serie, string $tipoNotaFiscal)
    {
        try {
            $conn = $this->notaFiscalEntityHandler->getDoctrine()->getConnection();
            $conn->beginTransaction();

            // Ex.: sequenciaNumNF_HOM_NFE_40
            $chave = 'sequenciaNumNF_' . $ambiente . '_' . $tipoNotaFiscal . '_' . $serie;

            $rs = $this->selectAppConfigSequenciaNumNFForUpdate($chave);

            if (!$rs || !$rs[0]) {
                $appConfig = new AppConfig();
                $appConfig->setAppUUID($_SERVER['CROSIERAPP_UUID']);
                $appConfig->setChave($chave);
                $appConfig->setValor(1);
                $this->appConfigEntityHandler->save($appConfig);
                $rs = $this->selectAppConfigSequenciaNumNFForUpdate($chave);
            }
            $prox = $rs[0]['valor'];
            $configId = $rs[0]['id'];

            // Verificação se por algum motivo a numeração na fis_nf já não está pra frente...
            $ultimoNaBase = null;
            $sqlUltimoNumero = 'SELECT max(numero) as numero FROM fis_nf WHERE documento_emitente = :documento_emitente AND ambiente = :ambiente AND serie = :serie AND tipo = :tipoNotaFiscal';

            $rUltimoNumero = $conn->fetchAll($sqlUltimoNumero,
                [
                    'documento_emitente' => $documentoEmiente,
                    'ambiente' => $ambiente,
                    'serie' => $serie,
                    'tipoNotaFiscal' => $tipoNotaFiscal
                ]);
            $ultimoNaBase = $rUltimoNumero[0]['numero'] ?? 0;
            if ($ultimoNaBase && $ultimoNaBase !== $prox) {
                $prox = $ultimoNaBase; // para não pular numeração a toa
            }
            $prox++;

            $updateSql = 'UPDATE cfg_app_config SET valor = :valor WHERE id = :id';
            $conn->executeUpdate($updateSql, ['valor' => $prox, 'id' => $configId]);
            $conn->commit();

            return $prox;
        } catch (\Exception $e) {
            $this->notaFiscalEntityHandler->getDoctrine()->rollback();
            $this->logger->error($e);
            $this->logger->error('Erro ao pesquisar próximo número de nota fiscal para [' . $ambiente . '] [' . $serie . '] [' . $tipoNotaFiscal . ']');
            throw new \RuntimeException('Erro ao pesquisar próximo número de nota fiscal para [' . $ambiente . '] [' . $serie . '] [' . $tipoNotaFiscal . ']');
        }
    }

    /**
     * @param string $chave
     * @return mixed
     */
    public function selectAppConfigSequenciaNumNFForUpdate(string $chave)
    {
        // FOR UPDATE para garantir que ninguém vai alterar este valor antes de terminar esta transação
        $sql = 'SELECT id, valor FROM cfg_app_config WHERE app_uuid = :app_uuid AND chave LIKE :chave FOR UPDATE';
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('valor', 'valor');
        $rsm->addScalarResult('id', 'id');
        $query = $this->notaFiscalEntityHandler->getDoctrine()->createNativeQuery($sql, $rsm);
        $query->setParameter('app_uuid', $_SERVER['CROSIERAPP_UUID']);
        $query->setParameter('chave', $chave);
        return $query->getResult();
    }

    /**
     * Gera uma fatura e suas movimentações a partir dos dados de uma nota fiscal.
     *
     * @param NotaFiscal $notaFiscal
     * @throws ViewException
     */
    public function gerarFatura(NotaFiscal $notaFiscal)
    {
        if ($notaFiscal->jsonData['fatura'] ?? false) {
            if ($notaFiscal->jsonData['fatura']['fatura_id'] ?? false) {
                throw new ViewException('Nota Fiscal com fatura já vinculada: ' . $notaFiscal->jsonData['fatura']['fatura_id']);
            }

            $conn = $this->movimentacaoEntityHandler->getDoctrine()->getConnection();
            $conn->beginTransaction();


            try {
                $fatura = [];
                $fatura['json_data']['notaFiscal_id'] = $notaFiscal->getId();
                $fatura['json_data']['notaFiscal_nFat'] = $notaFiscal->jsonData['fatura']['nFat'];
                $fatura['json_data'] = json_encode($fatura['json_data']);
                $fatura['dt_fatura'] = $notaFiscal->getDtEmissao()->format('Y-m-d');
                $fatura['fechada'] = true;
                $fatura['inserted'] = (new \DateTime())->format('Y-m-d H:i:s');
                $fatura['updated'] = (new \DateTime())->format('Y-m-d H:i:s');
                $fatura['user_inserted_id'] = $this->nfeUtils->security->getUser() ? $this->nfeUtils->security->getUser()->getId() : 1;
                $fatura['user_updated_id'] = $this->nfeUtils->security->getUser() ? $this->nfeUtils->security->getUser()->getId() : 1;
                $fatura['estabelecimento_id'] = 1;

                $conn->insert('fin_fatura', $fatura);
                $faturaId = $conn->lastInsertId();

                $doctrine = $this->movimentacaoEntityHandler->getDoctrine();

                $repoFatura = $doctrine->getRepository(Fatura::class);
                /** @var Fatura $fatura */
                $fatura = $repoFatura->find($faturaId);


                $repoTipoLancto = $doctrine->getRepository(TipoLancto::class);
                /** @var TipoLancto $tipoLancto_parcelamento */
                $tipoLancto_parcelamento = $repoTipoLancto->findOneBy(['codigo' => 21]);

                $repoModo = $doctrine->getRepository(Modo::class);
                /** @var Modo $modo_boleto */
                $modo_boleto = $repoModo->findOneBy(['codigo' => 6]);

                $repoCarteira = $doctrine->getRepository(Carteira::class);
                /** @var Carteira $carteira_indefinida */
                $carteira_indefinida = $repoCarteira->findOneBy(['codigo' => 99]);

                $repoCategoria = $doctrine->getRepository(Categoria::class);
                /** @var Categoria $categoria_CustosMercadoria */
                $categoria_CustosMercadoria = $repoCategoria->findOneBy(['codigo' => 202001]);

                $repoCentroCusto = $doctrine->getRepository(CentroCusto::class);
                /** @var CentroCusto $centroCusto */
                $centroCusto = $repoCentroCusto->findOneBy(['codigo' => 1]);

                $qtdeTotal = count($notaFiscal->jsonData['fatura']['duplicatas']);
                $i = 1;
                foreach ($notaFiscal->jsonData['fatura']['duplicatas'] as $duplicada) {

                    $movimentacao = new Movimentacao();

                    $movimentacao->setFatura($fatura);
                    $movimentacao->setTipoLancto($tipoLancto_parcelamento);
                    $movimentacao->setModo($modo_boleto);
                    $movimentacao->setCarteira($carteira_indefinida);
                    $movimentacao->setCategoria($categoria_CustosMercadoria);
                    $movimentacao->setCentroCusto($centroCusto);
                    $movimentacao->setStatus('ABERTA');

                    $movimentacao->setDtMoviment($notaFiscal->getDtEmissao());
                    $movimentacao->setDtVencto(DateTimeUtils::parseDateStr($duplicada['dVenc']));
                    $movimentacao->setValor($duplicada['vDup']);
                    $movimentacao->setParcelamento(true);
                    $movimentacao->setCadeiaOrdem($i);
                    $movimentacao->setCadeiaQtde($qtdeTotal);

                    $movimentacao->jsonData['notafiscal_id'] = $notaFiscal->getId();

                    $movimentacao->setDescricao('DUPLICATA ' . $duplicada['nDup'] . ' DE ' . $notaFiscal->getXNomeEmitente() . ' ' . StringUtils::strpad($i, 2) . '/' . StringUtils::strpad($qtdeTotal, 2));

                    $movimentacao->setQuitado(false);
                    $this->movimentacaoEntityHandler->save($movimentacao);
                    $i++;
                }

                $notaFiscal->jsonData['fatura']['fatura_id'] = $faturaId;
                $this->notaFiscalEntityHandler->save($notaFiscal);


                $conn->commit();
            } catch (\Exception $e) {
                if ($e instanceof ViewException) {
                    throw $e;
                }
                $this->syslog->err('Erro ao gerar fatura', $e->getTraceAsString());
            }
        }
    }

}
