<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Fiscal;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Messenger\CrosierQueueHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\APIUtils\CrosierApiResponse;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\Cte;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\DistDFe;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\FinalidadeNF;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\ModalidadeFrete;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalEvento;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\CteEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\DistDFeEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalEventoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalItemEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\DistDFeRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use NFePHP\NFe\Common\Tools as ToolsCommon;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class DistDFeBusiness
{

    private EntityManagerInterface $doctrine;

    private DistDFeEntityHandler $distDFeEntityHandler;

    private NotaFiscalEntityHandler $notaFiscalEntityHandler;

    private NotaFiscalItemEntityHandler $notaFiscalItemEntityHandler;

    private SyslogBusiness $logger;

    private NFeUtils $nfeUtils;

    private NotaFiscalEventoEntityHandler $notaFiscalEventoEntityHandler;

    private CrosierQueueHandler $crosierQueueHandler;

    private CteEntityHandler $cteEntityHandler;


    public function __construct(EntityManagerInterface        $doctrine,
                                DistDFeEntityHandler          $distDFeEntityHandler,
                                NotaFiscalEntityHandler       $notaFiscalEntityHandler,
                                NotaFiscalItemEntityHandler   $notaFiscalItemEntityHandler,
                                SyslogBusiness                $logger,
                                NFeUtils                      $nfeUtils,
                                NotaFiscalEventoEntityHandler $notaFiscalEventoEntityHandler,
                                CrosierQueueHandler           $crosierQueueHandler,
                                CteEntityHandler              $cteEntityHandler)
    {
        $this->doctrine = $doctrine;
        $this->distDFeEntityHandler = $distDFeEntityHandler;
        $this->notaFiscalEntityHandler = $notaFiscalEntityHandler;
        $this->notaFiscalItemEntityHandler = $notaFiscalItemEntityHandler;
        $this->logger = $logger->setApp('radx')->setComponent(self::class);
        $this->nfeUtils = $nfeUtils;
        $this->notaFiscalEventoEntityHandler = $notaFiscalEventoEntityHandler;
        $this->crosierQueueHandler = $crosierQueueHandler;
        $this->cteEntityHandler = $cteEntityHandler;
    }

    public function setEchoToLogger(): void
    {
        $this->logger->setEcho(true);
    }

    /**
     * @param string $cnpj
     * @return int
     * @throws ViewException
     */
    public function obterDistDFesAPartirDoUltimoNSU(string $cnpj, ?bool $ctes = false): int
    {
        /** @var DistDFeRepository $repo */
        $repo = $this->doctrine->getRepository(DistDFe::class);
        $ultNSU = $repo->findUltimoNSU($cnpj, $ctes);
        return $this->obterDistDFes($ultNSU, $cnpj, $ctes);
    }


    /**
     * Obtém as DistDFes emitidas contra o CNPJ a partir do $nsu informado
     *
     * @param int $nsu
     * @param string $cnpj
     * @return int
     * @throws ViewException
     */
    public function obterDistDFes(int $nsu, string $cnpj, ?bool $ctes = false): int
    {
        $qtdeObtida = 0;

        try {
            /** @var ToolsCommon $tools */
            $tools = $this->nfeUtils->getToolsByCNPJ($cnpj, $ctes);
            $tools->model('55');
            $tools->setEnvironment(1);
            /** @var DistDFeRepository $repo */
            $repo = $this->doctrine->getRepository(DistDFe::class);
            $iCount = 0; //executa a busca de DFe em loop
            // $nsu--; // decrementa, pois o webservice retorna a partir do próximo
            /**
             * O processo de busca deve ser executado em LOOP pois cada solicitação pode retornar no máximo 50
             * documentos cada, até que o numero do NSU recebido seja igual ao maxNSU disponível.
             */
            do {
                if ($iCount === 5) { // máximo de 5 * 50 (para respeitar as regras na RF e tbm não travar o servidor)
                    break;
                }
                $iCount++;
                $this->logger->info("Obtendo distDFes a partir do NSU: " . $nsu . " (CNPJ: " . $cnpj . ")");
                $resp = $tools->sefazDistDFe($nsu);
                $xmlResp = simplexml_load_string($resp);
                $xmlResp->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
                $r = $xmlResp->xpath('//soap:Body'); // aqui tenho o ultNSU e maxNSU
                // ultNSU: último consultado
                // maxNSU: último na base da sefaz

                $ret = null;
                try {
                    $ret = $r[0]->nfeDistDFeInteresseResponse->nfeDistDFeInteresseResult->retDistDFeInt;
                } catch (\Exception $e) {
                    try {
                        $ret = $r[0]->cteDistDFeInteresseResponse->cteDistDFeInteresseResult->retDistDFeInt;
                    } catch (\Exception $e) {
                        $err = 'Erro ao obter distDFes. Erro ao obter resposta parseada do xml (NSU: ' . $nsu . ' - ctes: ' . $ctes . ' - CNPJ: ' . $cnpj . ')';
                        $this->logger->error($err);
                        $this->logger->error($e->getMessage());
                        throw new ViewException($err);
                    }
                }

                if ($ret->cStat->__toString() === '137') {
                    $this->logger->info('Nenhum documento localizado (NSU: ' . $nsu . ') CNPJ: ' . $cnpj);
                    // xMotivo: Nenhum documento localizado
                    return 0;
                }

                if (!($ret->loteDistDFeInt->docZip ?? false)) {
                    $this->logger->info('Interrompendo a busca (NSU: ' . $nsu . ')');
                    if ($ret->xMotivo ?? false) {
                        $this->logger->info('Motivo: ' . $ret->xMotivo);
                        throw new ViewException($ret->xMotivo);
                    }
                    break;
                }

                $qtdeDocs = $ret->loteDistDFeInt->docZip->count();
                $this->logger->info('Obtidos ' . $qtdeDocs . ' documentos (NSU: ' . $nsu . ') CNPJ: ' . $cnpj);

                for ($i = 0; $i < $qtdeDocs; $i++) {
                    $doc = $ret->loteDistDFeInt->docZip[$i];
                    $nsu = (int)$doc->attributes()['NSU'];
                    $existe = $repo->findOneByFiltersSimpl([
                        ['nsu', 'EQ', $nsu],
                        ['cte', 'EQ', $ctes],
                        ['documento', 'EQ', $cnpj],
                    ]);
                    if (!$existe) {
                        $xml = $doc->__toString();
                        $dfe = new DistDFe();
                        $dfe->nsu = $nsu;
                        $dfe->cte = $ctes;
                        $dfe->xml = $xml;
                        $dfe->documento = $cnpj;
                        $this->distDFeEntityHandler->save($dfe);
                        $qtdeObtida++;
                    }
                }
                if ($qtdeDocs < 50) {
                    break;
                }
                sleep(5);
            } while (true);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao obter DFes (NSU: ' . $nsu . ') para o CNPJ: ' . $cnpj);
            $this->logger->error('CNPJ: ' . $cnpj . ' - ' . $e->getMessage());
            if ($e instanceof ViewException) {
                throw $e;
            }
            // else
            throw new ViewException('CNPJ: ' . $cnpj . ' - Erro ao obter DFes (NSU: ' . $nsu . ')');
        }
        $this->logger->info($qtdeObtida . ' distDFe(s) obtido(s) com sucesso para o CNPJ: ' . $cnpj);
        return $qtdeObtida;
    }


    /**
     * @param string $cnpj
     * @return int
     * @throws ViewException
     */
    public function obterDistDFesDeNSUsPulados(string $cnpj): int
    {
        $nsusPulados = $this->getNSUsPulados($cnpj);
        $qtde = 0;
        foreach ($nsusPulados as $nsu) {
            $this->obterDistDFeByNSU($nsu, $cnpj);
            $qtde++;
            sleep(3);
        }
        return $qtde;
    }

    /**
     * @return array
     * @throws ViewException
     */
    public function getNSUsPulados(string $cnpj): array
    {
        /** @var DistDFeRepository $repo */
        $repo = $this->doctrine->getRepository(DistDFe::class);
        $rNsus = $repo->findAllNSUs($cnpj);
        $nsus = [];
        foreach ($rNsus as $r) {
            $nsus[] = $r['nsu'];
        }
        $pulados = [];
        $primeiro = $nsus[0];
        $ultimo = $nsus[count($nsus) - 1];
        for ($i = $primeiro; $i < $ultimo; $i++) {
            if (!in_array($i, $nsus)) {
                $pulados[] = $i;
            }
        }

        return $pulados;
    }


    /**
     *
     * @param int $nsu
     * @param string $cnpj
     * @return bool
     * @throws ViewException
     */
    public function verificarNSUsNaSefaz(string $cnpj)
    {
        try {
            $tools = $this->nfeUtils->getToolsByCNPJ($cnpj);
            $tools->model('55');
            $tools->setEnvironment(1);

            /** @var DistDFeRepository $repo */
            $repo = $this->doctrine->getRepository(DistDFe::class);

            $resp = $tools->sefazDistDFe(0, 1);
            $xmlResp = simplexml_load_string($resp);
            $xmlResp->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
            return $xmlResp->xpath('//soap:Body');
        } catch (\Exception $e) {
            $this->logger->error('Erro ao verificarNSUsNaSefaz (CNPJ: ' . $cnpj . ')');
            $this->logger->error($e->getMessage());
            throw new ViewException('Erro ao verificarNSUsNaSefaz (CNPJ: ' . $cnpj . ')');
        }
    }

    /**
     *
     * @param int $nsu
     * @param string $cnpj
     * @return bool
     * @throws ViewException
     */
    public function obterDistDFeByNSU(int $nsu, string $cnpj): JsonResponse
    {
        try {
            $tools = $this->nfeUtils->getToolsByCNPJ($cnpj);
            $tools->model('55');
            $tools->setEnvironment(1);

            /** @var DistDFeRepository $repo */
            $repo = $this->doctrine->getRepository(DistDFe::class);

            $resp = $tools->sefazDistDFe(0, $nsu); // para trazer somente 1
            $xmlResp = simplexml_load_string($resp);
            $xmlResp->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
            $r = $xmlResp->xpath('//soap:Body');

            if ($ret->loteDistDFeInt->docZip ?: false) {
                $doc = $ret->loteDistDFeInt->docZip[0];
                $nsuRetornado = (int)$doc->attributes()['NSU'];
                if ($nsuRetornado === $nsu) {
                    $xml = $doc->__toString();
// gzdecode(base64_decode($xml))
                    $existe = $repo->findOneBy(['nsu' => $nsu, 'documento' => $cnpj]);
                    if (!$existe) {
                        $dfe = new DistDFe();
                        $dfe->nsu = $nsu;
                        $dfe->xml = $xml;
                        $dfe->documento = $cnpj;
                        $this->distDFeEntityHandler->save($dfe);
                    } else {
                        return CrosierApiResponse::success($r, 'NSU já existente na base');
                    }
                    return CrosierApiResponse::success($r, 'NSU obtido e salvo');
                } else {
                    return CrosierApiResponse::error(null, null, "NSU consultado difere do retornado (?)", $r);
                }
            } else {
                return CrosierApiResponse::error(null, null, "NSU não encontrado (?)", $r);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao obter DFe (NSU: ' . $nsu . ')');
            $this->logger->error($e->getMessage());
            return CrosierApiResponse::error($e, true, 'Erro ao obter DFe (NSU: ' . $nsu . ')', $r ?? null);
        }
    }

    /**
     * @param DistDFe $distDFe
     * @throws ViewException
     */
    public function reprocessarDistDFe(DistDFe $distDFe): void
    {
        switch ($distDFe->tipoDistDFe) {
            case 'NFEPROC':
                $nf = $this->nfeProc2NotaFiscal($distDFe->documento, $distDFe->getXMLDecoded(), $distDFe->notaFiscal, $distDFe);
                $distDFe->notaFiscal = $nf;
                $distDFe->status = 'PROCESSADO';
                $this->distDFeEntityHandler->save($distDFe);
                break;
            case 'RESNFE':
                $this->resNfe2NotaFiscal($distDFe);
                break;
            case 'PROCEVENTONFE':
            case 'RESEVENTO':
                $this->reprocessarDistDFeDeEvento($distDFe);
                break;
        }

    }

    /**
     * XML de NF completa!
     * Processa um elemento do tipo nfeProc (que pode vir de um DistDFe ou de uma nota fiscal baixada).
     * @throws ViewException
     */
    public function nfeProc2NotaFiscal(string $cnpjEmUso, \SimpleXMLElement $xml, NotaFiscal $nf = null, ?DistDFe $distDFe = null): ?NotaFiscal
    {
        if ($xml->getName() === 'NFe') {
            $this->logger->info('xml não é "nfeProc", e sim "NFe". Alterando apenas para poder importar...');
            $xmlStr = strtr($xml->asXML(),
                [
                    '<NFe' => '<nfeProc versao="4.00" xmlns="http://www.portalfiscal.inf.br/nfe"><NFe',
                    '</NFe>' => '</NFe></nfeProc>'
                ]);
            $xml = new \SimpleXMLElement($xmlStr);
        }

        $chaveAcesso = substr($xml->NFe->infNFe['Id']->__toString(), 3);
        if (!$nf) {
            $nf = $this->doctrine->getRepository(NotaFiscal::class)->findOneBy(['chaveAcesso' => $chaveAcesso]);
            if (!$nf) {
                $nf = new NotaFiscal();
            }
        }

        $nf_jsonData = $nf->jsonData ?? [];

        $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($cnpjEmUso);
        $ambiente = $nfeConfigs['tpAmb'] === 1 ? 'PROD' : 'HOM';
        $nf->ambiente = $ambiente;
        $nf->resumo = false;
        $nf->setXmlNota($xml->asXML());

        if ($xml->NFe->infNFe->ide->mod->__toString() !== '65') {
            $nf->documentoDestinatario = $xml->NFe->infNFe->dest->CNPJ;
            $nf->xNomeDestinatario = $xml->NFe->infNFe->dest->xNome;
            $nf->inscricaoEstadualDestinatario = $xml->NFe->infNFe->dest->IE;
            $nf->emailDestinatario = $xml->NFe->infNFe->dest->email;
            if ($xml->NFe->infNFe->dest->enderDest) {
                $nf->logradouroDestinatario = $xml->NFe->infNFe->dest->enderDest->xLgr;
                $nf->numeroDestinatario = $xml->NFe->infNFe->dest->enderDest->nro;
                $nf->bairroDestinatario = $xml->NFe->infNFe->dest->enderDest->xBairro;
                $nf->cidadeDestinatario = $xml->NFe->infNFe->dest->enderDest->xMun;
                $nf->estadoDestinatario = $xml->NFe->infNFe->dest->enderDest->UF;
                $nf->cepDestinatario = $xml->NFe->infNFe->dest->enderDest->CEP;
                $nf->foneDestinatario = $xml->NFe->infNFe->dest->enderDest->fone;
            }
        }

        $numNf = (int)$xml->NFe->infNFe->ide->nNF->__toString();
        if (!$numNf) {
            throw new ViewException('numNf n/d');
        }
        $nf->numero = $numNf;
        $nf->cnf = ((int)$xml->NFe->infNFe->ide->cNF->__toString());
        $mod = (int)$xml->NFe->infNFe->ide->mod->__toString();
        $nf->tipoNotaFiscal = ($mod === 55 ? 'NFE' : 'NFCE');

        $nf->entradaSaida = ($xml->NFe->infNFe->ide->tpNF->__toString() === 0 ? 'E' : 'S');
        $nf->protocoloAutorizacao = ($xml->NFe->infNFe->ide->nProt->__toString());

        $nf->serie = ((int)$xml->NFe->infNFe->ide->serie->__toString());
        $nf->naturezaOperacao = ($xml->NFe->infNFe->ide->natOp->__toString());
        $nf->dtEmissao = (DateTimeUtils::parseDateStr($xml->NFe->infNFe->ide->dhEmi->__toString()));
        $nf->dtSaiEnt = $nf->dtEmissao; // Não existe no XML?

        if ($xml->NFe->infNFe->ide->dhSaiEnt->__toString() ?: null) {
            $nf->dtSaiEnt = (DateTimeUtils::parseDateStr($xml->NFe->infNFe->ide->dhSaiEnt->__toString()));
        }
        $nf->finalidadeNf = (FinalidadeNF::getByCodigo($xml->NFe->infNFe->ide->finNFe->__toString())['key']);

        if ($xml->NFe->infNFe->ide->NFref->refNFe ?? null) {
            $nf->a03idNfReferenciada = ($xml->NFe->infNFe->ide->NFref->refNFe->__toString());
        }

        $nf->documentoEmitente = $xml->NFe->infNFe->emit->CNPJ;
        $nf->xNomeEmitente = $xml->NFe->infNFe->emit->xNome;
        $nf->inscricaoEstadualEmitente = $xml->NFe->infNFe->emit->IE;
        if ($xml->NFe->infNFe->emit->enderEmit) {
            $nf->logradouroEmitente = $xml->NFe->infNFe->emit->enderEmit->xLgr;
            $nf->numeroEmitente = $xml->NFe->infNFe->emit->enderEmit->nro;
            $nf->bairroEmitente = $xml->NFe->infNFe->emit->enderEmit->xBairro;
            $nf->cidadeEmitente = $xml->NFe->infNFe->emit->enderEmit->xMun;
            $nf->estadoEmitente = $xml->NFe->infNFe->emit->enderEmit->UF;
            $nf->cepEmitente = $xml->NFe->infNFe->emit->enderEmit->CEP;
            $nf->foneEmitente = $xml->NFe->infNFe->emit->enderEmit->fone;
        }

        $nf->chaveAcesso = $chaveAcesso;

        $nf->protocoloAutorizacao = $xml->protNFe->infProt->nProt ?? null;
        $nf->dtProtocoloAutorizacao = DateTimeUtils::parseDateStr($xml->protNFe->infProt->dhRecbto ?? null);

        /** @var NotaFiscal $nf */
        $nf = $this->notaFiscalEntityHandler->save($nf, false);

        $repoNotaFiscalItem = $this->doctrine->getRepository(NotaFiscalItem::class);

        foreach ($xml->NFe->infNFe->det as $iValue) {
            $item = $iValue;

            $ordem = (int)$item['nItem']->__toString();

            $nfItem = null;
            if ($nf->getId()) {
                $nfItem = $repoNotaFiscalItem->findOneByFiltersSimpl([
                    ['notaFiscal', 'EQ', $nf],
                    ['ordem', 'EQ', $ordem]
                ]);
            }

            if ($nfItem) {
                continue;
            }
            $nfItem = new NotaFiscalItem();
            $nfItem->notaFiscal = $nf;

            $nfItem->ordem = $ordem;
            $nfItem->codigo = $item->prod->cProd->__toString();
            $nfItem->ean = $item->prod->cEAN->__toString();
            $nfItem->descricao = $item->prod->xProd->__toString();
            $nfItem->ncm = $item->prod->NCM->__toString();
            $nfItem->cfop = $item->prod->CFOP->__toString();
            $nfItem->unidade = $item->prod->uCom->__toString();
            $nfItem->qtde = (float)$item->prod->qCom->__toString();
            $nfItem->valorUnit = (float)$item->prod->vUnCom->__toString();
            $nfItem->valorTotal = (float)$item->prod->vProd->__toString();
            $nfItem->valorDesconto = (float)$item->prod->vDesc->__toString();

            $this->notaFiscalEntityHandler->handleSavingEntityId($nfItem);

            $nf->addItem($nfItem);

            $this->notaFiscalItemEntityHandler->save($nfItem, false);
        }

        // FRETE
        $nf->transpModalidadeFrete = ModalidadeFrete::getByCodigo($xml->NFe->infNFe->transp->modFrete->__toString())['key'] ?? null;

        if ($xml->NFe->infNFe->transp->vol->qVol ?? null) {
            $nf->transpQtdeVolumes = (float)$xml->NFe->infNFe->transp->vol->qVol->__toString();
        }
        if ($xml->NFe->infNFe->transp->vol->esp ?? null) {
            $nf->transpEspecieVolumes = $xml->NFe->infNFe->transp->vol->esp->__toString();
        }
        if ($xml->NFe->infNFe->transp->vol->marca) {
            $nf->transpMarcaVolumes = $xml->NFe->infNFe->transp->vol->marca->__toString();
        }
        if ($xml->NFe->infNFe->transp->vol->nVol ?? null) {
            $nf->transpNumeracaoVolumes = $xml->NFe->infNFe->transp->vol->nVol;
        }
        if ($xml->NFe->infNFe->transp->vol->pesoL ?? null) {
            $nf->transpPesoLiquido = (float)$xml->NFe->infNFe->transp->vol->pesoL->__toString();
        }
        if ($xml->NFe->infNFe->transp->vol->pesoB ?? null) {
            $nf->transpPesoBruto = (float)$xml->NFe->infNFe->transp->vol->pesoB->__toString();
        }
        if ($xml->NFe->infNFe->transp->transporta->xNome ?? null) {
            $nf->transpNome = $xml->NFe->infNFe->transp->transporta->xNome->__toString();
        }
        if ($xml->NFe->infNFe->transp->transporta->CNPJ ?? null) {
            $nf->transpDocumento = $xml->NFe->infNFe->transp->transporta->CNPJ->__toString();
        }
        if ($xml->NFe->infNFe->transp->transporta->IE ?? null) {
            $nf->transpInscricaoEstadual = $xml->NFe->infNFe->transp->transporta->IE->__toString();
        }
        if ($xml->NFe->infNFe->transp->transporta->xEnder ?? null) {
            $nf->transpEndereco = $xml->NFe->infNFe->transp->transporta->xEnder->__toString();
        }
        if ($xml->NFe->infNFe->transp->transporta->xMun ?? null) {
            $nf->transpCidade = $xml->NFe->infNFe->transp->transporta->xMun->__toString();
        }
        if ($xml->NFe->infNFe->transp->transporta->xMun ?? null) {
            $nf->transpEstado = $xml->NFe->infNFe->transp->transporta->UF->__toString();
        }

        if ($xml->NFe->infNFe->cobr->fat ?? null) {
            $nf_jsonData['fatura'] = [
                'nFat' => $xml->NFe->infNFe->cobr->fat->nFat->__toString(),
                'vOrig' => $xml->NFe->infNFe->cobr->fat->vOrig->__toString(),
                'vDesc' => $xml->NFe->infNFe->cobr->fat->vDesc->__toString(),
                'vLiq' => $xml->NFe->infNFe->cobr->fat->vLiq->__toString()
            ];
            foreach ($xml->NFe->infNFe->cobr->dup as $dup) {
                $nf_jsonData['fatura']['duplicatas'][] = [
                    'nDup' => $dup->nDup->__toString(),
                    'dVenc' => $dup->dVenc->__toString(),
                    'vDup' => $dup->vDup->__toString(),
                ];
            }
        }

        $valorPago = (float)($xml->NFe->infNFe->pag->detPag->vPag ?? $xml->NFe->infNFe->pag->vPag ?? 0.0);

        $nf->valorTotal = $valorPago;

        if ($xml->NFe->infNFe->infAdic->infCpl ?? null) {
            $nf->infoCompl = $xml->NFe->infNFe->infAdic->infCpl->__toString();
        }

        if ($distDFe) {
            $nf_jsonData['distdfe_id'] = $distDFe->getId();
            $nf_jsonData['distdfe_tipo'] = 'nfeProc';
        }

        $nf->jsonData = $nf_jsonData;

        /** @var NotaFiscal $nf */
        $nf = $this->notaFiscalEntityHandler->save($nf);

        return $nf;
    }


    /**
     * XML de um CTe.
     * Processa um elemento do tipo cteProc.
     * @throws ViewException
     */
    public function cteProc2Cte(string $cnpjEmUso, \SimpleXMLElement $xml, Cte $cte = null, ?DistDFe $distDFe = null): ?Cte
    {
        $chaveAcesso = $xml->protCTe->infProt->chCTe->__toString();
        if (!$cte) {
            $cte = $this->doctrine->getRepository(Cte::class)->findOneBy(['chaveAcesso' => $chaveAcesso]);
            if (!$cte) {
                $cte = new Cte();
            }
        }

        $jsonData = $cte->jsonData ?? [];

        $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($cnpjEmUso);
        $ambiente = $nfeConfigs['tpAmb'] === 1 ? 'PROD' : 'HOM';
        $cte->ambiente = $ambiente;
        $cte->setXml($xml->asXML());

        $cte->documentoDestinatario = $xml->CTe->infCte->dest->CNPJ;
        $cte->xNomeDestinatario = $xml->CTe->infCte->dest->xNome;
        $cte->inscricaoEstadualDestinatario = $xml->CTe->infCte->dest->IE;
        $cte->emailDestinatario = $xml->CTe->infCte->dest->email;
        if ($xml->CTe->infCte->dest->enderDest) {
            $cte->logradouroDestinatario = $xml->CTe->infCte->dest->enderDest->xLgr;
            $cte->numeroDestinatario = $xml->CTe->infCte->dest->enderDest->nro;
            $cte->bairroDestinatario = $xml->CTe->infCte->dest->enderDest->xBairro;
            $cte->cidadeDestinatario = $xml->CTe->infCte->dest->enderDest->xMun;
            $cte->estadoDestinatario = $xml->CTe->infCte->dest->enderDest->UF;
            $cte->cepDestinatario = $xml->CTe->infCte->dest->enderDest->CEP;
            $cte->foneDestinatario = $xml->CTe->infCte->dest->enderDest->fone;
        }

        $nct = (int)$xml->CTe->infCte->ide->nCT->__toString();
        if (!$nct) {
            throw new ViewException('nCT n/d');
        }
        $cte->numero = $nct;

        $mod = (int)$xml->CTe->infCte->ide->mod->__toString();
        $cte->tipoCte = $mod;

        $cte->serie = ((int)$xml->CTe->infCte->ide->serie->__toString());
        $cte->naturezaOperacao = ($xml->CTe->infCte->ide->natOp->__toString());
        $cte->dtEmissao = (DateTimeUtils::parseDateStr($xml->CTe->infCte->ide->dhEmi->__toString()));

        $cte->documentoEmitente = $xml->CTe->infCte->emit->CNPJ;
        $cte->xNomeEmitente = $xml->CTe->infCte->emit->xNome;
        $cte->inscricaoEstadualEmitente = $xml->CTe->infCte->emit->IE;
        if ($xml->CTe->infCte->emit->enderEmit) {
            $cte->logradouroEmitente = $xml->CTe->infCte->emit->enderEmit->xLgr;
            $cte->numeroEmitente = $xml->CTe->infCte->emit->enderEmit->nro;
            $cte->bairroEmitente = $xml->CTe->infCte->emit->enderEmit->xBairro;
            $cte->cidadeEmitente = $xml->CTe->infCte->emit->enderEmit->xMun;
            $cte->estadoEmitente = $xml->CTe->infCte->emit->enderEmit->UF;
            $cte->cepEmitente = $xml->CTe->infCte->emit->enderEmit->CEP;
            $cte->foneEmitente = $xml->CTe->infCte->emit->enderEmit->fone;
        }

        $cte->chaveAcesso = $chaveAcesso;

        /** @var Cte $cte */
        $cte = $this->cteEntityHandler->save($cte, false);

        $valorPago = (float)($xml->CTe->infCte->vPrest->vTPrest->__toString() ?? 0.0);

        $cte->valorTotal = $valorPago;

        if ($distDFe) {
            $jsonData['distdfe_id'] = $distDFe->getId();
            $jsonData['distdfe_tipo'] = 'cteProc';
        }

        $cte->jsonData = $jsonData;

        /** @var Cte $cte */
        $cte = $this->cteEntityHandler->save($cte);

        return $cte;
    }


    /**
     * Se o XML for de resumo...
     *
     * @param DistDFe $distDFe
     * @return DistDFe
     * @throws ViewException
     */
    public function resNfe2NotaFiscal(DistDFe $distDFe): DistDFe
    {
        try {
            $xml = $distDFe->getXMLDecoded();
            if (!$xml) {
                throw new ViewException('Erro ao fazer o parse do xml para NF (chave: ' . $distDFe->chave . ')');
            }

            if ($distDFe->notaFiscal) {
                $nf = $distDFe->notaFiscal;
            } else {
                /** @var NotaFiscalRepository $repoNotaFiscal */
                $repoNotaFiscal = $this->doctrine->getRepository(NotaFiscal::class);
                /** @var NotaFiscal $nf */
                $nf = $repoNotaFiscal->findOneBy(['chaveAcesso' => $distDFe->chave]);
                if (!$nf) {
                    $nf = new NotaFiscal();
                }
            }
            $nf->setXmlNota($distDFe->xml);
            $nf->chaveAcesso = $distDFe->chave;
            $nf->nsu = $distDFe->nsu;
            $nf->resumo = true;

            $nf->dtEmissao = DateTimeUtils::parseDateStr($xml->dhEmi->__toString());

            $nf->entradaSaida = $xml->tpNF->__toString() === 0 ? 'E' : 'S';
            $nf->protocoloAutorizacao = $xml->nProt->__toString();

            $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($distDFe->documento);
            $nf->documentoDestinatario = (preg_replace("/[^0-9]/", '', $nfeConfigs['cnpj']));
            $nf->xNomeDestinatario = $nfeConfigs['razaosocial'];
            $nf->inscricaoEstadualDestinatario = $nfeConfigs['ie'];

            if ($xml->CNPJ ?? null) {
                $nf->documentoEmitente = $xml->CNPJ->__toString();
            }
            if ($xml->CPF ?? null) {
                $nf->documentoEmitente = $xml->CPF->__toString();
            }
            $nf->xNomeEmitente = $xml->xNome->__toString();
            if ($xml->IE ?? null) {
                $nf->inscricaoEstadualEmitente = $xml->IE->__toString();
            }

            $nf->valorTotal = (float)$xml->vNF->__toString();

            /** @var NotaFiscal $nf */
            $nf = $this->notaFiscalEntityHandler->save($nf);
            $distDFe->status = 'PROCESSADO';
            $distDFe->notaFiscal = $nf;

        } catch (\Throwable $e) {
            $this->logger->error('Erro - resNfe2NotaFiscal - distDFe->id: ' . $distDFe->getId());
            $distDFe->status = 'ERRO AO PROCESSAR';
        }

        return $this->distDFeEntityHandler->save($distDFe);
    }

    /**
     * @param DistDFe $distDFe
     * @throws ViewException
     */
    public function reprocessarDistDFeDeEvento(DistDFe $distDFe): void
    {
        try {

            if (strpos($distDFe->tipoDistDFe, 'EVENTO') === FALSE) {
                throw new ViewException('DistDFe não é sobre evento');
            }

            /** @var NotaFiscalRepository $repoNotaFiscal */
            $repoNotaFiscal = $this->doctrine->getRepository(NotaFiscal::class);
            /** @var NotaFiscal $nf */
            $nf = $repoNotaFiscal->findOneBy(['chaveAcesso' => $distDFe->chave]);
            if (!$nf) {
                throw new ViewException('Erro ao reprocessar. Evento para NF que não consta no BD (chave: ' . $distDFe->chave . ')');
            }

            $nfEvento = $distDFe->notaFiscalEvento ?? new NotaFiscalEvento();

            $xml = $distDFe->getXMLDecoded();
            if (!$xml) {
                throw new ViewException('XML inválido para reprocessarDistDFeDeEvento');
            }
            $xmlName = $xml->getName();
            $tpEvento = null;
            $nSeqEvento = null;
            $descEvento = null;
            $detEventoXJust = null;
            if ($xmlName === 'resEvento') {
                $tpEvento = (int)$xml->tpEvento->__toString();
                $nSeqEvento = (int)$xml->nSeqEvento->__toString();
                $descEvento = $xml->xEvento->__toString();
            }
            if ($xmlName === 'procEventoNFe') {
                $tpEvento = (int)$xml->evento->infEvento->tpEvento->__toString();
                $nSeqEvento = (int)$xml->evento->infEvento->nSeqEvento->__toString();
                $descEvento = $xml->evento->infEvento->detEvento->descEvento->__toString();
                $detEventoXJust = $xml?->evento?->infEvento?->detEvento?->xJust?->__toString();
            }
            if (!$tpEvento || !$nSeqEvento) {
                throw new ViewException('tpEvento, nSeqEvento ou descEvento não encontrados (tpEvento = ' . $tpEvento . ', nSeqEvento = ' . $nSeqEvento . ')' . ', descEvento = ' . $descEvento . ')');
            }

            try {
                $nfEvento->setXml($distDFe->xml);
                $nfEvento->notaFiscal = $nf;
                $nfEvento->tpEvento = $tpEvento;
                $nfEvento->nSeqEvento = $nSeqEvento;
                $nfEvento->descEvento = $descEvento;
                $this->notaFiscalEventoEntityHandler->save($nfEvento);
                $distDFe->nSeqEvento = $nSeqEvento;
                $distDFe->tpEvento = $tpEvento;
                $distDFe->notaFiscalEvento = $nfEvento;
                $distDFe->notaFiscal = $nfEvento->notaFiscal;
                $distDFe->status = 'PROCESSADO';
                if ($tpEvento === 110111) {
                    $nf->cStat = 101;
                    $nf->xMotivo = $descEvento;
                    $nf->motivoCancelamento = $detEventoXJust;
                    $this->notaFiscalEntityHandler->save($nf);
                }
            } catch (\Exception $e) {
                throw new ViewException('Erro ao salvar fis_nf ou fis_distdfe (chave ' . $distDFe->chave . ')');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao reprocessar DistDFe: salvando evento para NFe (chave ' . $distDFe->chave . ')');
            $this->logger->error($e->getMessage());
            $distDFe->status = 'ERRO AO PROCESSAR';
        }

        $this->distDFeEntityHandler->save($distDFe);


    }

    /**
     * Processo que extrai a DFe e salva como uma entidade NotaFiscal ou como um NotaFiscalEvento.
     *
     * @throws ViewException
     */
    public function processarDistDFesObtidos(?string $cnpj = null): void
    {
        $cnpjEmUso = $cnpj ?? $this->nfeUtils->getNFeConfigsEmUso()['cnpj'];
        $this->extrairChaveETipoDosDistDFes($cnpjEmUso);
        // Primeiro processa os DistDFes dos tipos NFEPROC e RESNFE
        $this->processarDistDFes($cnpjEmUso);

        // Depois processa os DistDFes dos tipos PROCEVENTONFE e RESEVENTO
        $this->processarDistDFesParaEventos($cnpjEmUso);
    }

    /**
     * Extrai a DFe e salva como uma entidade NotaFiscal.
     *
     * @throws ViewException
     */
    public function processarDistDFes(string $cnpjEmUso): void
    {
        $this->logger->info('Iniciando DistDFeBusiness::processarDistDFesParaNFes() para o CNPJ ' . $cnpjEmUso . '...');
        $distDFesAProcessar = [];
        try {
            /** @var DistDFeRepository $repoDistDFe */
            $repoDistDFe = $this->doctrine->getRepository(DistDFe::class);
            $distDFesAProcessar = $repoDistDFe->findDistDFeNaoProcessados($cnpjEmUso);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao findDistDFeNaoProcessados()');
            $this->logger->error($e->getMessage());
            throw new ViewException('Erro ao findDistDFeNaoProcessados()');
        }
        $this->logger->info(count($distDFesAProcessar) . ' distDFe(s) a processar...'); 
        if ($distDFesAProcessar) {
            $total = count($distDFesAProcessar);
            $i = 1;
            $this->logger->info($total . ' distDFe(s) a processar...');
            foreach ($distDFesAProcessar as $distDFeId) {
                try {
                    $this->logger->debug("Processando " . $i++ . " de " . $total . ". id = " . $distDFeId['id']);
                    /** @var DistDFe $distDFe */
                    $distDFe = $repoDistDFe->find($distDFeId);// gzdecode(base64_decode($distDFe->getXml()))
                    $xml = $distDFe->getXMLDecoded();
                    if (!$xml) {
                        $this->logger->debug('Sem XML. Pulando...');
                        continue;
                    }
                    $xmlName = $xml->getName();
                    if ($xmlName === 'nfeProc') {
                        try {
                            $this->logger->debug('XML é um nfeProc... Chamando nfeProc2NotaFiscal...');
                            $nf = $this->nfeProc2NotaFiscal($cnpjEmUso, $distDFe->getXMLDecoded(), null, $distDFe);
                            $distDFe->notaFiscal = $nf;
                            $this->distDFeEntityHandler->save($distDFe);
                            $this->logger->info('CrosierQueue: DistDFe processado: ' . $distDFe->chave . ' (chamando radx.fiscal.eventos.nova_nf_com_xml)');
                            $this->crosierQueueHandler->post('radx.fiscal.eventos.nova_nf_com_xml', ['id' => $nf->getId()]);
                        } catch (\Exception $e) {
                            $this->logger->debug('Erro ao processar nfeProc para notaFiscal');
                            $this->logger->debug($e->getMessage());
                        }
                    } elseif ($xmlName === 'resNFe') {
                        try {
                            $this->logger->debug('XML é um nfeProc... Chamando resNfe2NotaFiscal...');
                            $this->resNfe2NotaFiscal($distDFe);
                        } catch (\Exception $e) {
                            $this->logger->debug('Erro ao processar resNFe para notaFiscal');
                            $this->logger->debug($e->getMessage());
                        }
                    } elseif ($xmlName === 'cteProc') {
                        try {
                            $this->logger->debug('XML é um cteProc... Chamando cteProc2Cte...');
                            $cte = $this->cteProc2Cte($cnpjEmUso, $distDFe->getXMLDecoded(), null, $distDFe);
                            $distDFe->fisCte = $cte;
                            $this->distDFeEntityHandler->save($distDFe);
                        } catch (\Exception $e) {
                            $this->logger->debug('Erro ao processar cteProc para cte');
                            $this->logger->debug($e->getMessage());
                        }
                    } else {
                        $this->logger->error('Erro ao processar DistDFe: não reconhecido (id ' . $distDFe->getId() . ')');
                    }
                } catch (ViewException $e) {
                    $this->logger->error('Erro geral ao processar DistDFe ' . $distDFe->getId() . '). Continuando...');
                }
            }
        }
    }

    /**
     *
     */
    public function processarDistDFesParaEventos(string $cnpjEmUso): void
    {
        /** @var DistDFeRepository $repoDistDFe */
        $repoDistDFe = $this->doctrine->getRepository(DistDFe::class);

        $distDFesAProcessar = $repoDistDFe->findDistDFeNotInNotaFiscalEvento($cnpjEmUso);

        /** @var NotaFiscalRepository $repoNotaFiscal */
        $repoNotaFiscal = $this->doctrine->getRepository(NotaFiscal::class);

        /** @var DistDFe $distDFe */
        foreach ($distDFesAProcessar as $distDFeId) {
            try {
                /** @var DistDFe $distDFe */
                $distDFe = $repoDistDFe->find($distDFeId);
                /** @var NotaFiscal $nf */
                $nf = $repoNotaFiscal->findOneBy(['chaveAcesso' => $distDFe->chave]);
                if (!$nf) {
                    throw new ViewException('Erro ao processar. Evento para NF que não consta no BD (chave: ' . $distDFe->chave . ')');
                }
                $xml = $distDFe->getXMLDecoded();
                if (!$xml) {
                    continue;
                }
                $xmlName = $xml->getName();
                $tpEvento = null;
                $nSeqEvento = null;
                $descEvento = null;
                if ($xmlName === 'resEvento') {
                    $tpEvento = (int)$xml->tpEvento->__toString();
                    $nSeqEvento = (int)$xml->nSeqEvento->__toString();
                    $descEvento = $xml->xEvento->__toString();
                }
                if ($xmlName === 'procEventoNFe') {
                    $tpEvento = (int)$xml->evento->infEvento->tpEvento->__toString();
                    $nSeqEvento = (int)$xml->evento->infEvento->nSeqEvento->__toString();
                    $descEvento = $xml->evento->infEvento->detEvento->descEvento->__toString();
                }
                if (!$tpEvento || !$nSeqEvento) {
                    throw new ViewException('tpEvento, nSeqEvento ou descEvento não encontrados (tpEvento = ' . $tpEvento . ', nSeqEvento = ' . $nSeqEvento . ')' . ', descEvento = ' . $descEvento . ')');
                }

                try {
                    $nfEvento = new NotaFiscalEvento();
                    $nfEvento->setXml($distDFe->xml);
                    $nfEvento->notaFiscal = $nf;
                    $nfEvento->tpEvento = $tpEvento;
                    $nfEvento->nSeqEvento = $nSeqEvento;
                    $nfEvento->descEvento = $descEvento;
                    $this->notaFiscalEventoEntityHandler->save($nfEvento);

                    $distDFe->nSeqEvento = $nSeqEvento;
                    $distDFe->tpEvento = $tpEvento;
                    $distDFe->notaFiscalEvento = $nfEvento;
                    $distDFe->status = 'PROCESSADO';
                } catch (\Throwable $e) {
                    throw new ViewException('Erro ao salvar fis_nf ou fis_distdfe (chave ' . $distDFe->chave . ')');
                }
            } catch (\Throwable $e) {
                $this->logger->error('Erro ao processar DistDFe: salvando evento para NFe (chave ' . $distDFe->chave . ')');
                $this->logger->error($e->getMessage());
                $distDFe->status = 'ERRO AO PROCESSAR';
            }

            $this->distDFeEntityHandler->save($distDFe);
        }
    }

    /**
     * Download da DFe pela chave (utilizado após a manifestação da nota e sua subsequente autorização de download).
     *
     * @param NotaFiscal $notaFiscal
     * @throws ViewException
     */
    public function downloadNFe(NotaFiscal $notaFiscal): void
    {
        try {
            $tools = $this->nfeUtils->getToolsByCNPJ($notaFiscal->documentoDestinatario);
            $tools->model('55');
            $tools->setEnvironment(1);
            $response = $tools->sefazDownload($notaFiscal->chaveAcesso);
            $xmlDownload = simplexml_load_string($response);
            $xmlDownload->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
            $xml = $xmlDownload->xpath('//soap:Body');

            $cStat = $xml[0]->nfeDistDFeInteresseResponse->nfeDistDFeInteresseResult->retDistDFeInt->cStat ?? null;
            if (!$cStat || !$cStat->__toString()) {
                $this->logger->info('Erro ao obter cStat para chave: ' . $notaFiscal->chaveAcesso . ')');
            }
            $cStat = $cStat->__toString();

            if ($cStat !== '138') {
                $this->logger->info('cStat diferente de 138 para chave ' . $notaFiscal->chaveAcesso . ' (cStat = ' . $cStat . ')');
                $xMotivo = $xml[0]->nfeDistDFeInteresseResponse->nfeDistDFeInteresseResult->retDistDFeInt->xMotivo ?? null;
                if ($xMotivo instanceof \SimpleXMLElement) {
                    $this->logger->info('xMotivo: ' . $xMotivo->__toString());
                }
            }

            $zip = $xml[0]->nfeDistDFeInteresseResponse->nfeDistDFeInteresseResult->retDistDFeInt->loteDistDFeInt->docZip ?? null;
            if ($zip) {
                $notaFiscal->setXmlNota($zip->__toString());
                $xml = $notaFiscal->getXMLDecoded();
                if ($xml->getName() === 'nfeProc') {
                    $this->nfeProc2NotaFiscal($notaFiscal->documentoDestinatario, $xml, $notaFiscal);
                } elseif ($xml->getName() === 'resNFe') {
                    $repoDistDfe = $this->doctrine->getRepository(DistDFe::class);
                    $distDfe = $repoDistDfe->findOneByFiltersSimpl([['chave', 'EQ', $notaFiscal->chaveAcesso]]);
                    $this->resNfe2NotaFiscal($distDfe);
                }
            } else {
                $this->logger->error('Erro ao obter XML (download zip) para a chave: ' . $notaFiscal->chaveAcesso);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao fazer o download do XML (chave: ' . $notaFiscal->chaveAcesso . ')');
            $this->logger->error($e->getMessage());
            throw new ViewException('Erro ao fazer o download do XML (chave: ' . $notaFiscal->chaveAcesso . ')');
        }
    }

    /**
     * Descompacta o xml e procura o tipo, chave e dados de evento.
     *
     * @throws ViewException
     */
    public function extrairChaveETipoDosDistDFes(string $cnpjEmUso): void
    {
        $this->logger->info('Extraindo chave e tipo dos DistDFes para o CNPJ ' . $cnpjEmUso . '...');
        /** @var DistDFeRepository $repo */
        $repo = $this->doctrine->getRepository(DistDFe::class);
        $distDFesSemChave = $repo->findDistDfesSemChavePorCnpj($cnpjEmUso);
        $qtdeDistDFesSemChave = count($distDFesSemChave);
        $this->logger->info("Temos " . $qtdeDistDFesSemChave . " DistDFes sem chave para processar...");
        $nfeConfigs = $this->nfeUtils->getNFeConfigsByCNPJ($cnpjEmUso);
        /** @var DistDFe $distDFe */
        foreach ($distDFesSemChave as $i => $distDFe) {
            try {
                $xml = $distDFe->getXMLDecoded();
                if (!$xml) {
                    continue;
                }
                $chave = null;
                $cnpj = null;
                $xmlName = $xml->getName();
                $this->logger->info("Processando " . ($i + 1) . " de " . $qtdeDistDFesSemChave . ". id = " . $distDFe->getId() . " - xmlName = " . $xmlName);
                if ($xmlName === 'nfeProc') {
                    $chave = $xml->protNFe->infProt->chNFe->__toString();
                    $cnpj = $xml->NFe->infNFe->emit->CNPJ->__toString();
                } elseif ($xmlName === 'resNFe') {
                    $chave = $xml->chNFe->__toString();
                    $cnpj = $xml->CNPJ->__toString();
                } elseif ($xmlName === 'resEvento') {
                    $chave = $xml->chNFe->__toString();
                    $cnpj = $xml->CNPJ->__toString();
                    $distDFe->tpEvento = (int)$xml->tpEvento->__toString();
                    $distDFe->nSeqEvento = (int)$xml->nSeqEvento->__toString();
                } elseif ($xmlName === 'procEventoNFe') {
                    $chave = $xml->evento->infEvento->chNFe->__toString();
                    $cnpj = $xml->evento->infEvento->CNPJ->__toString();
                    $distDFe->tpEvento = (int)$xml->evento->infEvento->tpEvento->__toString();
                    $distDFe->nSeqEvento = (int)$xml->evento->infEvento->nSeqEvento->__toString();
                } elseif ($xmlName === 'procEventoCTe') {
                    $chave = $xml->eventoCTe->infEvento->chCTe->__toString();
                    $cnpj = $xml->eventoCTe->infEvento->CNPJ->__toString();
                    $distDFe->tpEvento = (int)$xml->eventoCTe->infEvento->tpEvento->__toString();
                    $distDFe->nSeqEvento = (int)$xml->eventoCTe->infEvento->nSeqEvento->__toString();
                } elseif ($xmlName === 'cteProc') {
                    $chave = $xml->protCTe->infProt->chCTe->__toString();
                    $cnpj = $xml->CTe->infCte->emit->CNPJ->__toString();
                } else {
                    throw new ViewException('Tipo de XML não reconhecido: ' . $xmlName);
                }
                $distDFe->proprio = $nfeConfigs['cnpj'] === $cnpj;
                $distDFe->tipoDistDFe = $xml->getName();
                $distDFe->chave = $chave;
                $this->distDFeEntityHandler->save($distDFe);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $this->logger->error('Erro ao extrair chave do DistDFe id=' . $distDFe->getId());
            }
        }
    }


    public function res2proc()
    {
        /** @var Connection $conn */
        $conn = $this->doctrine->getConnection();

        $sql = "
                SELECT 
                    n.id as notafiscal_id, d.id as distdfe_id 
                FROM 
                    fis_distdfe d, fis_nf n 
                WHERE
                     (JSON_IS_NULL_OR_EMPTY(json_data, 'distdfe_tipo') OR n.json_data->>\"$.distdfe_tipo\" NOT LIKE 'nfeProc') AND 
                    d.nota_fiscal_id = n.id AND tipo_distdfe = 'NFEPROC' AND chnfe IN 
                    (SELECT chnfe FROM (select count(*) as qt, chnfe FROM fis_distdfe WHERE nota_fiscal_id is not null GROUP BY documento, chnfe, nota_fiscal_id having qt > 1) duas_na_dist)
                    ORDER BY n.id DESC LIMIT 2000 
                    ";

        /** @var NotaFiscalRepository $repoNotaFiscal */
        $repoNotaFiscal = $this->doctrine->getRepository(NotaFiscal::class);

        /** @var DistDFeRepository $repoDistDFe */
        $repoDistDFe = $this->doctrine->getRepository(DistDFe::class);

        $rs = $conn->fetchAllAssociative($sql);
        foreach ($rs as $r) {
            $nf = $repoNotaFiscal->find($r['notafiscal_id']);
            $distdfe = $repoDistDFe->find(($r['distdfe_id']));

            $distDfe_xmlName = $distdfe->getXMLDecoded()->getName();

            if ($distDfe_xmlName !== 'nfeProc') {
                throw new ViewException('distdfe xmlName != nfeProc');
            }

            $nf_xmlName = $nf->getXMLDecoded()->getName();

            $mudouAlgo = false;

            if ($nf_xmlName !== 'nfeProc') {
                $nf->setXmlNota($distdfe->xml);
                $nf->jsonData['distdfe_id'] = $distdfe->getId();
                $nf->jsonData['distdfe_tipo'] = 'nfeProc';
                $this->notaFiscalEntityHandler->save($nf);
                $mudouAlgo = true;
            }

            if ($distdfe->getId() !== ($nf->jsonData['distdfe_id'] ?? null)) {
                $nf->jsonData['distdfe_id'] = $distdfe->getId();
                $mudouAlgo = true;
            }

            if (($nf->jsonData['distdfe_tipo'] ?? '') !== 'nfeProc') {
                $nf->jsonData['distdfe_tipo'] = 'nfeProc';
                $mudouAlgo = true;
            }

            if ($mudouAlgo) {
                $this->notaFiscalEntityHandler->save($nf);
            }


            $this->notaFiscalEntityHandler->getDoctrine()->clear();

//            $xmlNota = $nf->getXMLDecodedAsString();
//            $xmlDistDFe = @gzdecode(base64_decode($distdfe->xml));
//            

        }


    }


}
