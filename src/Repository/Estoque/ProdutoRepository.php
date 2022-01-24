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

    /**
     * @return mixed
     */
    public function getJsonMetadata()
    {
        /** @var AppConfigRepository $repoAppConfig */
        $repoAppConfig = $this->getEntityManager()->getRepository(AppConfig::class);
        return $repoAppConfig->findOneBy(
            [
                'appUUID' => $_SERVER['CROSIERAPPRADX_UUID'],
                'chave' => 'est_produto_json_metadata'
            ]
        )->valor;
    }

    public function getUnidadesSelect2js()
    {
        $arrUnidades = json_decode($this->getJsonMetadata(), true)['campos']['unidade']['sugestoes'];
        $arrUnidades = array_combine($arrUnidades, $arrUnidades);

        return json_encode(Select2JsUtils::arrayToSelect2Data($arrUnidades));
    }


    /**
     * @param string $str
     * @return array
     */
    public function findProdutosByNomeOuFinalCodigo_select2js(string $str, int $max = 20): array
    {
        $sql = 'SELECT prod.id, prod.codigo, prod.nome, preco.preco_prazo FROM est_produto prod LEFT JOIN est_produto_preco preco ON prod.id = preco.produto_id ' .
            'WHERE preco.atual AND (' .
            'prod.nome LIKE :nome OR ' .
            'prod.json_data->>"$.codigo" LIKE :codigo) ORDER BY prod.nome LIMIT ' . $max;

        $rs = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql,
            [
                'nome' => '%' . $str . '%',
                'codigo' => '%' . $str
            ]);

        $results = [];

        foreach ($rs as $r) {
            $jsonData = json_decode($r['json_data'], true);
            $results[] = [
                'id' => $r['id'],
                'text' => str_pad($r['codigo'], 13, '0', STR_PAD_LEFT) . ' - ' . $r['nome'],
                'preco_prazo' => $r['preco_prazo']
            ];
        }

        return $results;
    }

}
