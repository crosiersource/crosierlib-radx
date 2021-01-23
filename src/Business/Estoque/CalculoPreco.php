<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\Estoque;


use CrosierSource\CrosierLibBaseBundle\Utils\NumberUtils\DecimalUtils;
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
        if (!($preco['precoCusto'] ?? false)) {
            throw new \LogicException('Preço de custo nulo.');
        }

        $this->calcularCoeficiente($preco);

        if (!($preco['coeficiente'] ?? false)) {
            throw new \LogicException('É necessário o coeficiente para calcular os preços');
        }

        $coeficiente = $preco['coeficiente'];
        $custoFinanceiro = $preco['custoFinanceiro'];
        $custoFinanceiroCompl = 1.0 - (float)$custoFinanceiro;
        $scale = 4;
        $custoFinanceiroInv = bcdiv(1, $custoFinanceiroCompl, $scale);
        $precoCusto = $preco['precoCusto'];

        $custoFinanceiroInv = DecimalUtils::round($custoFinanceiroInv, $scale, DecimalUtils::ROUND_HALF_UP);
        $pc_cfinv = bcmul($precoCusto, $custoFinanceiroInv, $scale);
        $pc_cfinv_coef = (float)bcmul($pc_cfinv, $coeficiente, $scale);
        $pc_cfinv_coef = DecimalUtils::round($pc_cfinv_coef, $scale, DecimalUtils::ROUND_HALF_UP);

        $precoPrazo = DecimalUtils::round($pc_cfinv_coef, 2, DecimalUtils::ROUND_HALF_UP);
        $centavos = bcsub($precoPrazo, (int)$precoPrazo, 2);
        if (false && $centavos >= 0.9) {
            if (false && (float)$centavos === 0.95) {
                $precoPrazo_corrigido = ((int)$precoPrazo + 1);
            } else {
                $precoPrazo_corrigido = ((int)$precoPrazo + 0.9);
            }
        } else {
            $precoPrazo_corrigido = DecimalUtils::round($pc_cfinv_coef, 1, DecimalUtils::ROUND_UP);
        }
        $precoPrazo_centavosComDezenaExata = bcmul($precoPrazo_corrigido, 1, 1);

        $descontoAVista = 1.00 - 0.1;

        $precoVista = bcmul($precoPrazo_centavosComDezenaExata, $descontoAVista, 2);

        $preco['precoPrazo'] = $precoPrazo_centavosComDezenaExata;
        $preco['precoVista'] = $precoVista;
    }

    /**
     * coeficiente = ( 1.0 / (1.0 - custoOperacional + margem) ) * depreciacaoPrazo
     * @param array $preco
     */
    public function calcularCoeficiente(array &$preco): void
    {
        $prazo = (int)$preco['prazo'];
        // obtém o depreciacaoPrazo da base de dados
        if ($prazo === null || $prazo === '' || $prazo < 0) {
            throw new \LogicException('Prazo deve ser um número inteiro igual ou maior que 0.');
        }

        $margem = (float)$preco['margem'];
        if ($margem > 0.9999) {
            throw new \LogicException('Margem superior a 99,99%');
        }

        $custoOperacional = (float)$preco['custoOperacional']; // (float)bcdiv($preco['custoOperacional'], '100.0', 3);
        if ($custoOperacional < 0 || $custoOperacional > 0.99) {
            throw new \LogicException('Custo Operacional deve estar entre 0 e 0.99');
        }

        $depreciacaoPrazo = $this->doctrine->getRepository(DepreciacaoPreco::class)->findDepreciacaoByPrazo($prazo);

        // Antes a margem não era passada em número decimal
        $margemPorcent = $margem; // (float)bcdiv($margem, '100.00', 4);


        $margemMaximaPorcent = 1.0 - $custoOperacional - 0.0001;
        if ($margemPorcent > $margemMaximaPorcent) {
            throw new \LogicException('Margem não pode ser superior a ' . $margemMaximaPorcent . ' (C.O.: ' . $custoOperacional . ')');
        }

        $coefNaoDeflacionado = (float)bcsub('1.0', ($custoOperacional + $margemPorcent), 25);
        $coefNaoDeflacionadoInv = $coefNaoDeflacionado > 0 ? bcdiv('1.0', $coefNaoDeflacionado, 25) : 0;

        $coeficiente = DecimalUtils::round((float)bcmul($coefNaoDeflacionadoInv, $depreciacaoPrazo, 25), 3);
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
        $preco['custoOperacional'] = (float)$preco['custoOperacional'];
        if ($preco['custoOperacional'] < 0 || $preco['custoOperacional'] > 0.99) {
            throw new \LogicException('Custo Operacional deve estar entre 0 e 0.99');
        }
        /** @var DepreciacaoPrecoRepository $repoDepreciacaoPreco */
        $repoDepreciacaoPreco = $this->doctrine->getRepository(DepreciacaoPreco::class);
        $depreciacaoPrazo = $repoDepreciacaoPreco->findDepreciacaoByPrazo($preco['prazo']);

        $precoCusto = (float)$preco['precoCusto'];
        if (!($preco['precoPrazo'] ?? false)) {
            throw new \LogicException('Impossível calcular margem sem preço prazo');
        }
        $precoPrazo = (float)$preco['precoPrazo'];
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
