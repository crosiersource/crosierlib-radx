<?php

namespace CrosierSource\CrosierLibRadxBundle\Controller\Estoque;

use CrosierSource\CrosierLibBaseBundle\Utils\ExceptionUtils\ExceptionUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller interno do bundle com métodos/rotas que devem estar disponíveis em quaisquer apps.
 * 
 * @author Carlos Eduardo Pauluk
 */
class ProdutoController extends AbstractController
{
    
    /**
     * DiaUtilController constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function getUrlsImagens(Produto $produto): JsonResponse
    {
        try {

            if (!$produto->imagens) {
                $r = [
                    'RESULT' => 'OK',
                    'MSG' => 'Nenhuma imagem cadastrada para o produto',
                    'DATA' => []
                ];
                return new JsonResponse($r, 400);
            }

            $urls = [];
            $prefixo = ($_SERVER['CROSIERAPPRADX_URL'] ?? '??CROSIERAPPRADX_URL??') . '/images/produtos/' . $produto->depto->getId() . '/' . $produto->grupo->getId() . '/' . $produto->subgrupo->getId() . '/';
            foreach ($produto->imagens as $imagem) {
                $urls[] = $prefixo . $imagem->getImageName();
            }

            return new JsonResponse(
                [
                    'RESULT' => 'OK',
                    'MSG' => 'URLs obtidas com sucesso',
                    'DATA' => $urls
                ]
            );
        } catch (\Throwable $e) {
            $msg = ExceptionUtils::treatException($e);
            if (!$msg) {
                $msg = 'Erro - getUrlsImagens';
            }
            $r = [
                'RESULT' => 'ERR',
                'MSG' => $msg
            ];
            return new JsonResponse($r);
        }
    }


}
