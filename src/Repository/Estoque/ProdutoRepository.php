<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibBaseBundle\Utils\ViewUtils\Select2JsUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class ProdutoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Produto::class;
    }

    public function getJsonMetadata()
    {
        /** @var AppConfigRepository $repoAppConfig */
        $repoAppConfig = $this->getEntityManager()->getRepository(AppConfig::class);
        return $repoAppConfig->findOneBy(
            [
                'appUUID' => $_SERVER['CROSIERAPP_UUID'],
                'chave' => 'est_produto_json_metadata'
            ]
        )->getValor();
    }

    public function getUnidadesSelect2js()
    {
        $arrUnidades = json_decode($this->getJsonMetadata(), true)['campos']['unidade']['sugestoes'];
        $arrUnidades = array_combine($arrUnidades, $arrUnidades);

        return json_encode(Select2JsUtils::arrayToSelect2Data($arrUnidades));
    }


}
