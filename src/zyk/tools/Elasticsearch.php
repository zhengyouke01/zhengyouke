<?php


namespace zyk\tools;


use Elasticsearch\ClientBuilder;

class Elasticsearch extends ServiceBase implements BaseInterface {

    private $client = null;

    public function __construct() {
        $this->client = ClientBuilder::create()->build();
    }

    /**
     * 服务信息
     */
    public function serviceInfo() {
        return ['service_name' => 'Elasticsearch处理', 'service_class' => 'Elasticsearch', 'service_describe' => 'Elasticsearch处理', 'author' => 'GuJiaqiang', 'version' => '1.0'];
    }


    /**
     * 保存文档
     * @author GJQ 2020-05-15
     *
     * @param $index 索引名称
     * @param $id 文档ID
     * @param array $data 更新内容
     * @return array
     */
    public function indexDoc($index, $id, $data = []) {

        $params = [
            'index' => $index,
            'type' => '_doc',
            'id' => $id,
            'body' => $data
        ];

        $result = $this->client->index($params);
        return $result;
    }

    /**
     * 删除文档
     * @author GJQ 2020-05-15
     *
     * @param $index 索引名称
     * @param $id 文档ID
     * @return array
     */
    public function deleteDoc($index, $id) {

        $params = [
            'index' => $index,
            'type' => '_doc',
            'id' => $id
        ];

        $result = $this->client->delete($params);
        return $result;
    }

    /**
     * 根据条件删除文档
     * @author GJQ 2020-05-15
     *
     * @param $index
     * @param array $data
     * @return array
     */
    public function deleteByQuery($index, $data = []) {
        $params = [
            'index' => $index,
            'type' => '_doc',
            'body' => $data
        ];

        $result = $this->client->deleteByQuery($params);

        return $result;
    }

    /**
     * 根据条件查询
     * @author GJQ 2020-05-15
     *
     * @param array $index
     * @param $data
     * @return array
     */
    public function search($index = [], $data) {

        $params = [
            'index' => $index,
            'type' => '_doc',
            'body' => $data
        ];

        $result = $this->client->search($params);
        return $result;
    }





}
