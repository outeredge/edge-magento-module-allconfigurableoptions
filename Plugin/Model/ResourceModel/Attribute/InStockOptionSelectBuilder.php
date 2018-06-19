<?php

namespace OuterEdge\ConfigProduct\Plugin\Model\ResourceModel\Attribute;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use \Magento\ConfigurableProduct\Plugin\Model\ResourceModel\Attribute\InStockOptionSelectBuilder as MageInStockOptionSelectBuilder;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionSelectBuilderInterface;
use Magento\Framework\DB\Select;

class InStockOptionSelectBuilder extends MageInStockOptionSelectBuilder
{
    /**
     * CatalogInventory Stock Status Resource Model.
     *
     * @var Status
     */
    private $stockStatusResource;
    /**
     *
     * @var Configuration
     */
    private $stockConfiguration;
    /**
     * @param Status $stockStatusResource
     */

    public function __construct(Status $stockStatusResource, StockConfigurationInterface $stockConfiguration)
    {
        $this->stockStatusResource = $stockStatusResource;
        $this->stockConfiguration = $stockConfiguration;
    }
    /**
     * Add stock status filter to select.
     *
     * @param OptionSelectBuilderInterface $subject
     * @param Select $select
     * @return Select
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSelect(OptionSelectBuilderInterface $subject, Select $select)
    {
        //Ignore the stock status in the configuration option, if the show out of stock is set.
        if (!$this->stockConfiguration->isShowOutOfStock()) {
            $select->joinInner(
                ['stock' => $this->stockStatusResource->getMainTable()],
                'stock.product_id = entity.entity_id',
                []
            )->where(
                'stock.stock_status = ?',
                \Magento\CatalogInventory\Model\Stock\Status::STATUS_IN_STOCK
            );
        }
        return $select;
    }
}