<?php

namespace CrosierSource\CrosierLibRadxBundle\ApiPlatform\DataProvider\Financeiro;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Clinica\Especialidade;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Saldo;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\SaldoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CarteiraRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\MovimentacaoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\SaldoRepository;

/**
 * @author Carlos Eduardo Pauluk
 */
class SaldoDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{

    private SaldoEntityHandler $saldoEntityHandler;

    public function __construct(SaldoEntityHandler $saldoEntityHandler)
    {
        $this->saldoEntityHandler = $saldoEntityHandler;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Saldo::class === $resourceClass &&
            ($context['filters']['carteira'] ?? null) &&
            ($context['filters']['dtSaldo'] ?? null);
    }

    /**
     * @throws ViewException
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        try {
            $conn = $this->saldoEntityHandler->getDoctrine()->getConnection();
            $saldos = [];

            if (!is_array($context['filters']['dtSaldo'])) {
                $dtSaldo = $context['filters']['dtSaldo'];
                $context['filters']['dtSaldo'] = ['after' => $dtSaldo, 'before' => $dtSaldo];
            }
            $dtIni = DateTimeUtils::parseDateStr($context['filters']['dtSaldo']['after']);
            $dtFim = DateTimeUtils::parseDateStr($context['filters']['dtSaldo']['before']);
            if (DateTimeUtils::dataMaiorQue($dtIni, $dtFim)) {
                throw new \Exception('dtIni > dtFim');
            }

            $carteiraId = substr($context['filters']['carteira'], strrpos($context['filters']['carteira'], '/') + 1);

            /** @var SaldoRepository $repoSaldo */
            $repoSaldo = $this->saldoEntityHandler->getDoctrine()->getRepository(Saldo::class);
            
            /** @var CarteiraRepository $repoCarteira */
            $repoCarteira = $this->saldoEntityHandler->getDoctrine()->getRepository(Carteira::class);
            $carteira = $repoCarteira->find($carteiraId);

            $rDtConsolidado = $conn->fetchAssociative('SELECT dt_consolidado FROM fin_carteira WHERE id = :carteiraId', ['carteiraId' => $carteiraId]);
            $dtConsolidado = DateTimeUtils::parseDateStr($rDtConsolidado['dt_consolidado'] ?? '1900-01-01');

            $todosOsDias = DateTimeUtils::getDatesList($dtIni, $dtFim);

            $movimentacaoRepo = null;

            $saldos = $conn->fetchAllAssociative(
                'SELECT id, dt_saldo, total_realizadas, total_pendencias ' .
                'FROM fin_saldo ' .
                'WHERE ' .
                'carteira_id = :carteiraId AND ' .
                'dt_saldo BETWEEN :dtIni AND :dtFim ' .
                'ORDER BY dt_saldo',
                [
                    'carteiraId' => $carteiraId,
                    'dtIni' => $dtIni->format('Y-m-d'),
                    'dtFim' => $dtFim->format('Y-m-d'),
                ]);

            $saldoByData = [];
            foreach ($saldos as $saldo) {
                $saldoByData[$saldo['dt_saldo']] = $saldo;
            }


            $agora = new \DateTime();
            foreach ($todosOsDias as $dia) {

                $tem = false;

                /** @var Saldo $saldo */
                foreach ($saldos as $saldo) {
                    if ($saldo['dt_saldo'] === $dia->format('Y-m-d')) {
                        $tem = true;
                        break;
                    }
                }

                if (!$tem || DateTimeUtils::dataMaiorQue($dia, $dtConsolidado)) {
                    /** @var MovimentacaoRepository $movimentacaoRepo */
                    $movimentacaoRepo = $movimentacaoRepo ?? $this->saldoEntityHandler->getDoctrine()->getRepository(Movimentacao::class);
                    $saldoPosterior = (float)$movimentacaoRepo->findSaldo($dia, $carteiraId, 'SALDO_POSTERIOR_REALIZADAS') ?? 0;
                    $saldoPosteriorComCheques = (float)$movimentacaoRepo->findSaldo($dia, $carteiraId, 'SALDO_POSTERIOR_COM_CHEQUES') ?? 0;

                    // Só salva se mudou algo
                    if (!isset($saldoByData[$dia->format('Y-m-d')]) ||
                        (float)$saldoByData[$dia->format('Y-m-d')]['total_realizadas'] !== $saldoPosterior ||
                        (float)$saldoByData[$dia->format('Y-m-d')]['total_pendencias'] !== $saldoPosteriorComCheques) {

                        if (!isset($saldoByData[$dia->format('Y-m-d')])) {
                            $saldo = new Saldo();
                        } else {
                            $saldo = $repoSaldo->find($saldoByData[$dia->format('Y-m-d')]['id']);
                        }
                        $saldo->carteira = $carteira;
                        $saldo->dtSaldo = $dia;
                        $saldo->totalRealizadas = $saldoPosterior;
                        $saldo->totalPendencias = $saldoPosteriorComCheques;
                        $this->saldoEntityHandler->save($saldo);

                    }

                }
            }

            /** @var SaldoRepository $repoSaldo */
            $repoSaldo = $this->saldoEntityHandler->getDoctrine()->getRepository(Saldo::class);
            $saldos = $repoSaldo->findByFiltersSimpl([
                ['carteira', 'EQ', $carteira],
                ['dtSaldo', 'BETWEEN_DATE', [$dtIni, $dtFim]]
            ], ['dtSaldo' => 'ASC'], 0, null);

            return $saldos;
        } catch (\Exception $e) {
                throw new ViewException('Erro ao calcular saldos', 0, $e);
        }
    }
}
