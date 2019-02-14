<?php
/**
 * Url Rewrite Import admin controller
 *
 * @category    Jworks
 * @package     Jworks_UrlRewriteImport
 * @author Jitheesh V O <jitheeshvo@gmail.com>
 * @copyright Copyright (c) 2017 Jworks Digital ()
 */
namespace Jworks\UrlRewriteImport\Model\UrlRewrite;

use Magento\Framework\App\ResourceConnection;

/**
 * URL rewrite CSV Import Handler
 */
class CsvImportHandler
{
    /**
     * DB connection
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * Collection of publicly available stores
     *
     * @var \Magento\Store\Model\ResourceModel\Store\Collection
     */
    protected $_publicStores;

    /**
     * CSV Processor
     *
     * @var \Magento\Framework\File\Csv
     */
    protected $csvProcessor;

    /**
     * Customer entity DB table name.
     *
     * @var string
     */
    protected $_entityTable;
    /**
     * @var array
     */
    protected $_rewriteFields;

    const ENTITY_TYPE_CUSTOM = 'custom';

    /**
     * @param \Magento\Store\Model\ResourceModel\Store\Collection $storeCollection
     * @param \Magento\Framework\File\Csv $csvProcessor
     */
    public function __construct(
        \Magento\Store\Model\ResourceModel\Store\Collection $storeCollection,
        \Magento\Framework\File\Csv $csvProcessor,
        ResourceConnection $resource,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        array $data = []
    )
    {
        // prevent admin store from loading
        $this->_publicStores = $this->_populateStoreData($storeCollection->getData());
        $this->csvProcessor = $csvProcessor;
        $this->_connection =
            isset($data['connection']) ?
                $data['connection'] :
                $resource->getConnection();
        $this->_urlModel = $urlRewriteFactory->create();
        /** @var $urlResource \Magento\UrlRewrite\Model\ResourceModel\UrlRewrite */
        $urlResource = $this->_urlModel->getResource();
        $this->_entityTable = $urlResource->getMainTable();
        $this->_rewriteFields = ['request_path', 'target_path', 'redirect_type', 'store_id', 'entity_type'];
    }

    /**
     * @param $data
     * @return array
     */
    protected function _populateStoreData($data)
    {
        $stores = [];
        foreach ($data as $store) {
            $stores[$store['code']] = $store['store_id'];
        }
        return $stores;
    }

    /**
     * Import Tax Rates from CSV file
     *
     * @param array $file file info retrieved from $_FILES array
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importFromCsvFile($file)
    {
        if (!isset($file['tmp_name'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid file upload attempt.'));
        }
        $urlRawData = $this->csvProcessor->getData($file['tmp_name']);
        $urlRawData = $this->_prepareData($urlRawData);


        foreach ($urlRawData as $rowIndex => $dataRow) {

            $this->_validateRewrite($dataRow);
            $urlData = $this->parse($dataRow);
            $this->_importUrl($urlData);
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function _prepareData($data)
    {
        $keys = array_shift($data);

        foreach ($data as &$rewrite) {
            $rewrite = array_combine($keys, $rewrite);
        }

        return $data;
    }

    /**
     * @param array $rewrite
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _validateRewrite(array $rewrite)
    {
        if (count($rewrite) != count(array_filter($rewrite, 'strlen'))) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'Empty columns are not allowed.'
                )
            );
        }
    }

    /**
     * @param array $rewrite
     * @return array
     */
    public function parse(array $rewrite)
    {
        array_walk($rewrite, 'trim');;
        $parsedRewrite = [];
        $parsedRewrite['request_path'] = $rewrite['request_path'];
        $parsedRewrite['target_path'] = $rewrite['target_path'];
        $parsedRewrite['redirect_type'] = $rewrite['redirect'];
        $parsedRewrite['entity_type'] = self::ENTITY_TYPE_CUSTOM;
        $parsedRewrite['store_id'] = $this->_publicStores[$rewrite['store_code']];

        return $parsedRewrite;
    }

    /**
     * Import single rate
     *
     * @param array $urlData
     * @return array regions cache populated with regions related to country of imported tax rate
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _importUrl(array $urlData)
    {
        $this->_connection->insertOnDuplicate(
            $this->_entityTable,
            $urlData
        );
    }
}
