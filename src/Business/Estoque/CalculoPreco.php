<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\Estoque;


use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\DepreciacaoPreco;
use CrosierSource\CrosierLibRadxBundle\Repository\Estoque\DepreciacaoPrecoRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Lógicas para cálculo de preços.
 *
 * @author Carlos Eduardo Pauluk
 */
class CalculoPreco
{

    private EntityManagerInterface $doctrine;

    /**
     * CalculoPreco constructor.
     * @param EntityManagerInterface $doctrine
     */
    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }


    /**
     * Cálculo:
     * precoPrazo = precoCusto * custoFinanceiroCompl * coeficiente
     * custoFinanceiroCompl = 1.0 / (1.0 - 15%)
     * precoPrazo = precoCusto * (1 / (1 - 15%))
     * precoVista = precoPrazo * (1 - 10%)
     *
     * @param array $preco
     */
    public function calcularPreco(array &$preco): void
    {
        if (!($preco['margem'] ?? false)) {
            $this->calcularMargem($preco);
        }

        $this->calcularCoeficiente($preco);

        if (!($preco['coeficiente'] ?? false)) {
            throw new \LogicException('É necessário o coeficiente para calcular os preços');
        }

        $coeficiente = $preco['coeficiente'];
        $custoFinanceiro = $preco['custoFinanceiro'];
        $custoFinanceiroCompl = 1.0 - $custoFinanceiro;
        $custoFinanceiroInv = bcdiv(1, $custoFinanceiroCompl, 25);
        $precoCusto = $preco['precoCusto'];
        if (!$precoCusto) {
            throw new \LogicException('Preço de custo nulo.');
        }
        $custoFinanceiroInv = round($custoFinanceiroInv, 13);
        $pc_cfinv = bcmul($precoCusto, $custoFinanceiroInv, 13);
        $pc_cfinv_coef = (float)bcmul($pc_cfinv, $coeficiente, 13);
        $pc_cfinv_coef = round($pc_cfinv_coef, 2, PHP_ROUND_HALF_UP);
        $precoPrazo = round($pc_cfinv_coef, 1, PHP_ROUND_HALF_UP);

        $descontoAVista = 1.00 - 0.1;

        $precoVista = bcmul($precoPrazo, $descontoAVista, 2);

        $preco['precoPrazo'] = $precoPrazo;
        $preco['precoVista'] = $precoVista;
    }

    /**
     * coeficiente = ( 1.0 / (1.0 - custoOperacional + margem) ) * depreciacaoPrazo
     * @param array $preco
     */
    public function calcularCoeficiente(array &$preco): void
    {
        $preco['margem'] = (float) $preco['margem'];
        if ($preco['margem'] > 99.99) {
            throw new \LogicException('Margem superior a 99,99%');
        }

        // obtém o depreciacaoPrazo da base de dados
        if ($preco['prazo'] === null || $preco['prazo'] === '' || $preco['prazo'] < 0) {
            throw new \LogicException('Prazo deve ser um número inteiro igual ou maior que 0.');
        }
        $depreciacaoPrazo = $this->doctrine->getRepository(DepreciacaoPreco::class)->findDepreciacaoByPrazo($preco['prazo']);

        $margemPorcent = (float)bcdiv($preco['margem'], '100.00', 4);
        $custoOperacPorcent = (float)bcdiv($preco['custoOperacional'], '100.0', 3);

        $margemMaximaPorcent = 1.0 - $custoOperacPorcent - 0.0001;
        if ($margemPorcent > $margemMaximaPorcent) {
            throw new \LogicException('Margem não pode ser superior a ' . $margemMaximaPorcent . ' (C.O.: ' . $custoOperacPorcent . ')');
        }

        $coefNaoDeflacionado = (float)bcsub('1.0', ($custoOperacPorcent + $margemPorcent), 3);
        $coefNaoDeflacionadoInv = $coefNaoDeflacionado > 0 ? bcdiv('1.0', $coefNaoDeflacionado, 25) : 0;

        $coeficiente = round((float)bcmul($coefNaoDeflacionadoInv, $depreciacaoPrazo, 25), 3);
        // retorno
        $preco['coeficiente'] = $coeficiente;
    }

    /**
     * Calcula a margem a partir de um preço a prazo já dado.
     * Fórmula: margem = 1 - custoOperacional - (precoCusto * depreciacaoPrazo / precoPrazo * (1 - custoFinanceiro)).
     *
     * @param array $preco
     */
    public function calcularMargem(array &$preco): void
    {
        $preco['custoOperacional'] = (float) $preco['custoOperacional'];
        if ($preco['custoOperacional'] < 0 || $preco['custoOperacional'] > 0.99) {
            throw new \LogicException('Custo Operacional deve estar entre 0 e 0.99');
        }
        /** @var DepreciacaoPrecoRepository $repoDepreciacaoPreco */
        $repoDepreciacaoPreco = $this->doctrine->getRepository(DepreciacaoPreco::class);
        $depreciacaoPrazo = $repoDepreciacaoPreco->findDepreciacaoByPrazo($preco['prazo']);

        $precoCusto = $preco['precoCusto'];
        if (!($preco['precoPrazo'] ?? false)) {
            throw new \LogicException('Impossível calcular margem sem preço prazo');
        }
        $precoPrazo = $preco['precoPrazo'];
        $custoOperacionalCompl = (float)bcsub(1.0, $preco['custoOperacional'], 2);
        $custoFinanceiroCompl = (float)bcsub(1.0, $preco['custoFinanceiro'], 2);

        $a = bcmul($precoCusto, $depreciacaoPrazo, 12);
        $b = bcmul($precoPrazo, $custoFinanceiroCompl, 12);
        $div = bcdiv($a, $b, 12);

        $margem = bcsub($custoOperacionalCompl, $div, 4);
        $margem = bcmul($margem, '100.0', 2);
        $preco['margem'] = round($margem, 2);


//        $precoCusto_x_custoFinanceiroInv = bcmul($precoCusto, $custoFinanceiroInv, 13);
//
//        $depreciacaoPrazo_DIV_precoPrazo = $precoPrazo > 0 ? bcdiv($depreciacaoPrazo, $precoPrazo, 25) : 0.0;
//
//        $aux = bcmul($precoCusto_x_custoFinanceiroInv, $depreciacaoPrazo_DIV_precoPrazo, 25);
//
//        $custoOperacional = (float) bcdiv($custoOperacional, '100.0', 2);
//        $margem = (float)bcmul(1.0 - $custoOperacional - $aux, '100.0', 2);
//        if ($margem === 65.00) {
//            $margem -= 0.0001;
//        }
//        $preco['margem'] = $margem;
//        $this->calcularCoeficiente($preco);
//
//        $descontoAVista = 1.00 - 0.1;
//
//        $precoVista = bcmul($preco['precoPrazo'], $descontoAVista);
//        $preco['precoVista'] = $precoVista;
    }


}