<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Business\Financeiro\GrupoBusiness;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Grupo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\GrupoItemRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class GrupoEntityHandler
 *
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro
 * @author Carlos Eduardo Pauluk
 */
class GrupoEntityHandler extends EntityHandler
{

    private GrupoItemEntityHandler $grupoItemEntityHandler;


    public function __construct(ManagerRegistry        $doctrine,
                                Security               $security,
                                ParameterBagInterface  $parameterBag,
                                SyslogBusiness         $syslog,
                                GrupoItemEntityHandler $grupoItemEntityHandler)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog);
        $this->grupoItemEntityHandler = $grupoItemEntityHandler;
    }

    public function getEntityClass()
    {
        return Grupo::class;
    }


    public function afterSave(/** @var Grupo $grupo */ $grupo)
    {
        if ($grupo->itens === null || $grupo->itens->count() === 0) {
            $this->gerarNovo($grupo, true);
        }
    }


    /**
     * Gera um novo próximo item de grupo de movimentação.
     * @throws \Exception
     */
    public function gerarNovo(Grupo $pai, bool $prox = true): ?GrupoItem
    {
        try {
            $this->getDoctrine()->beginTransaction();

            $novo = new GrupoItem();
            $novo->pai = $pai;
            $novo->fechado = false;
            $novo->valorInformado = 0.0;

            /** @var GrupoItemRepository $repoGrupoItem */
            $repoGrupoItem = $this->getDoctrine()->getRepository(GrupoItem::class);

            if ($prox) {

                /** @var GrupoItem $ultimo */
                $ultimo = $repoGrupoItem->findOneBy(['pai' => $pai], ['dtVencto' => 'DESC']);

                if (!$ultimo) {
                    $proxDtVencto = new \DateTime();
                    $proxDtVencto->setDate($proxDtVencto->format('Y'), $proxDtVencto->format('m'), $pai->diaVencto);
                    $novo->carteiraPagante = $pai->carteiraPagantePadrao;
                } else {
                    $novo->anterior = $ultimo;
                    $proxDtVencto = clone $ultimo->dtVencto;
                    $proxDtVencto = $proxDtVencto->setDate($proxDtVencto->format('Y'), (int)$proxDtVencto->format('m') + 1, $proxDtVencto->format('d'));
                    $novo->carteiraPagante = $ultimo->carteiraPagante;
                }
                $novo->dtVencto = $proxDtVencto;
                $novo->dtVencto->setTime(0, 0);

                $novo->descricao = $pai->descricao . ' - ' . $proxDtVencto->format('d/m/Y');

                $this->grupoItemEntityHandler->save($novo);

                if ($ultimo) {
                    $ultimo->proximo = $novo;
                    $this->grupoItemEntityHandler->save($ultimo);
                }
            } else {
                /** @var GrupoItem $primeiro */
                $primeiro = $repoGrupoItem->findOneBy(['pai' => $pai], ['dtVencto' => 'ASC']);

                if (!$primeiro) {
                    $proxDtVencto = new \DateTime();
                    $proxDtVencto->setDate($proxDtVencto->format('Y'), $proxDtVencto->format('m'), $pai->diaVencto);
                    $novo->carteiraPagante = $pai->carteiraPagantePadrao;
                } else {
                    $novo->proximo = $primeiro;
                    $proxDtVencto = clone $primeiro->dtVencto;
                    $proxDtVencto = $proxDtVencto->setDate($proxDtVencto->format('Y'), (int)$proxDtVencto->format('m') - 1, $proxDtVencto->format('d'));
                    $novo->carteiraPagante = $primeiro->carteiraPagante;
                }
                $novo->dtVencto = $proxDtVencto;
                $novo->dtVencto->setTime(0, 0);

                $novo->descricao = $pai->descricao . ' - ' . $proxDtVencto->format('d/m/Y');

                $this->grupoItemEntityHandler->save($novo);

                if ($primeiro) {
                    $primeiro->anterior = $novo;
                    $this->grupoItemEntityHandler->save($primeiro);
                }
            }

            $this->getDoctrine()->commit();
            return $novo;
        } catch (\Exception $e) {
            $this->getDoctrine()->rollback();
            $erro = "Erro ao gerar novo item";
            throw new \Exception($erro, null, $e);
        }
    }


    public function gerarParaDtMoviment(Grupo $grupo, \DateTime $dtMoviment): GrupoItem
    {
        $mesVencto = GrupoBusiness::findDtVenctoByDtMoviment($grupo, $dtMoviment);
        $this->gerarDesdeAte($grupo, $mesVencto, $mesVencto);
        $repoGrupoItem = $this->getDoctrine()->getRepository(GrupoItem::class);
        return $repoGrupoItem->findByMesAnoAndGrupo($mesVencto, $grupo);
    }


    public function gerarDesdeAte(Grupo $grupo, \DateTime $mesAnoIni, \DateTime $mesAnoFim): void
    {
        $repoGrupo = $this->getDoctrine()->getRepository(Grupo::class);
        $repoGrupoItem = $this->getDoctrine()->getRepository(GrupoItem::class);
        $ini = $mesAnoIni;
        $fim = $mesAnoFim;
        $ultimo = $repoGrupo->findUltimoItemDoGrupo($grupo);
        if ($ultimo) {
            if (DateTimeUtils::ehAntesOuIgual($ultimo->dtVencto, $mesAnoIni)) {
                $ini = $ultimo->dtVencto;
            } elseif (DateTimeUtils::ehDepoisOuIgual($ultimo->dtVencto, $mesAnoFim)) {
                $fim = $ultimo->dtVencto;
            }
        }
        $meses = DateTimeUtils::getMonthsList($ini, $fim);
        $gruposItens = [];
        $anterior = null;
        foreach ($meses as $i => $mes) {
            $grupoItem = $repoGrupoItem->findByMesAnoAndGrupo($mes, $grupo);
            $dtVencto = GrupoBusiness::findDtVenctoByDtMoviment($grupo, $mes);
            $gruposItens[] = [
                'dtVencto' => $dtVencto,
                'grupoItem' => $grupoItem
            ];
        }
        foreach ($gruposItens as $i => $e) {
            if (!$e['grupoItem']) {
                $grupoItem = $this->criarPorDtVencto($grupo, $e['dtVencto']);
                $gruposItens[$i]['grupoItem'] = $grupoItem;
            }
        }

        foreach ($gruposItens as $i => $e) {
            if ($i > 0) {
                if (!$gruposItens[$i - 1]['grupoItem']->proximo ||
                    $gruposItens[$i - 1]['grupoItem']->proximo->getId() !== $e['grupoItem']->getId()) {
                    $gruposItens[$i - 1]['grupoItem']->proximo = $e['grupoItem'];
                    $gruposItens[$i - 1]['precisaSalvar'] = true;
                }
            }
            if ($i < count($gruposItens) - 2) {
                if (!$gruposItens[$i + 1]['grupoItem']->anterior ||
                    $gruposItens[$i + 1]['grupoItem']->anterior->getId() !== $e['grupoItem']->getId()) {
                    $gruposItens[$i + 1]['grupoItem']->anterior = $e['grupoItem'];
                    $gruposItens[$i + 1]['precisaSalvar'] = true;
                }
            }
            if (!$gruposItens[$i]['grupoItem']->getId()) {
                $gruposItens[$i]['precisaSalvar'] = true;
            }
        }

        foreach ($gruposItens as $e) {
            if ($e['precisaSalvar'] ?? false) {
                $this->grupoItemEntityHandler->save($e['grupoItem'], false);
            }
        }
        $this->grupoItemEntityHandler->save($e['grupoItem'], true);
    }

    private function criarPorDtVencto(
        Grupo     $grupo,
        \DateTime $dtVencto
    ): GrupoItem
    {
        $grupoItem = new GrupoItem();
        $grupoItem->pai = $grupo;
        $grupoItem->fechado = false;
        $grupoItem->valorInformado = 0.0;
        $grupoItem->dtVencto = $dtVencto;
        return $grupoItem;
    }


}