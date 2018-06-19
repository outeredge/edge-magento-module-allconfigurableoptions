<?php

namespace OuterEdge\ConfigProduct\Block\Product\View\Type;

use Magento\Swatches\Block\Product\Renderer\Configurable as SwatchConfigurable;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\Json\EncoderInterface;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\Catalog\Helper\Product as CatalogProduct;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\Swatch;
use Magento\Swatches\Model\SwatchAttributesProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\Format;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;

class Configurable extends SwatchConfigurable
{
    /**
     * @var SwatchAttributesProvider
     */
    private $swatchAttributesProvider;

    /**
     * @var Format
     */
    private $localeFormat;

    /**
     * @var StockRepository
     */
    protected $_stockRepository;

    /**
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param EncoderInterface $jsonEncoder
     * @param Data $helper
     * @param CatalogProduct $catalogProduct
     * @param CurrentCustomer $currentCustomer
     * @param PriceCurrencyInterface $priceCurrency
     * @param ConfigurableAttributeData $configurableAttributeData
     * @param SwatchData $swatchHelper
     * @param Media $swatchMediaHelper
     * @param array $data other data
     * @param SwatchAttributesProvider $swatchAttributesProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        EncoderInterface $jsonEncoder,
        Data $helper,
        CatalogProduct $catalogProduct,
        CurrentCustomer $currentCustomer,
        PriceCurrencyInterface $priceCurrency,
        ConfigurableAttributeData $configurableAttributeData,
        SwatchData $swatchHelper,
        Media $swatchMediaHelper,
        array $data = [],
        SwatchAttributesProvider $swatchAttributesProvider = null,
        Format $localeFormat = null,
        StockItemInterfaceFactory $stockItemInterfaceFactory
    ){
        $this->swatchHelper = $swatchHelper;
        $this->swatchMediaHelper = $swatchMediaHelper;
        $this->_stockRepository     = $stockItemInterfaceFactory->create();
        $this->swatchAttributesProvider = $swatchAttributesProvider
            ?: ObjectManager::getInstance()->get(SwatchAttributesProvider::class);
        $this->localeFormat = $localeFormat ?: ObjectManager::getInstance()->get(Format::class);

        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $helper,
            $catalogProduct,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $swatchHelper,
            $swatchMediaHelper,
            $data
        );
    }

    /**
     * Sets product stock status.
     *
     * @param   $productId
     *
     * @return  array
     */
    public function getStockItem($productId)
    {
        $stock_data                   = array();
        $stock_data['out_stock']      = 0 ;

        $stock = $this->_stockRepository->load($productId,'product_id');
        if (!$stock->getIsInStock()) {
            $stock_data['out_stock'] = 1;
        }

        return $stock_data;
    }

    /**
     * Get Product Stock
     *
     * @return array
     */
    public function getProductStock()
    {
        $stock = [];
        $skipSaleableCheck=true;
        $allProducts       = $this->getProduct()->getTypeInstance()->getUsedProducts($this->getProduct(), null);

        foreach ($allProducts as $product) {
            if ($product->isSaleable() || $skipSaleableCheck) {
                $stock[$product->getId()] = $this->getStockItem($product->getId());
            }
        }
        return $stock;
    }


    /**
     * Composes configuration for js
     *
     * @return string
     */
    public function getJsonConfig()
    {
        $store = $this->getCurrentStore();
        $currentProduct = $this->getProduct();

        $regularPrice = $currentProduct->getPriceInfo()->getPrice('regular_price');
        $finalPrice = $currentProduct->getPriceInfo()->getPrice('final_price');

        $options = $this->helper->getOptions($currentProduct, $this->getAllowProducts(),$this->getProductStock());
        $attributesData = $this->configurableAttributeData->getAttributesData($currentProduct, $options);

        $config = [
            'attributes' => $attributesData['attributes'],
            'template' => str_replace('%s', '<%- data.price %>', $store->getCurrentCurrency()->getOutputFormat()),
            'currencyFormat' => $store->getCurrentCurrency()->getOutputFormat(),
            'optionPrices' => $this->getOptionPrices(),
            'priceFormat' => $this->localeFormat->getPriceFormat(),
            'prices' => [
                'oldPrice' => [
                    'amount' => $this->localeFormat->getNumber($regularPrice->getAmount()->getValue()),
                ],
                'basePrice' => [
                    'amount' => $this->localeFormat->getNumber($finalPrice->getAmount()->getBaseAmount()),
                ],
                'finalPrice' => [
                    'amount' => $this->localeFormat->getNumber($finalPrice->getAmount()->getValue()),
                ],
            ],
            'productId' => $currentProduct->getId(),
            'chooseText' => __('Choose an Option...'),
            'images' => $this->getOptionImages(),
            'index' => isset($options['index']) ? $options['index'] : [],
            'stock' => isset($options['stock']) ? $options['stock'] : [],
        ];

        if ($currentProduct->hasPreconfiguredValues() && !empty($attributesData['defaultValues'])) {
            $config['defaultValues'] = $attributesData['defaultValues'];
        }

        $config = array_merge($config, $this->_getAdditionalConfig());

        return $this->jsonEncoder->encode($config);
    }

    /**
     * Get Allowed Products
     * Modified to set $skipSaleableCheck to  true
     *
     * @return \Magento\Catalog\Model\Product[]
     */
    public function getAllowProducts()
    {
        if (!$this->hasAllowProducts()) {
            $products = [];
            //$skipSaleableCheck = $this->catalogProduct->getSkipSaleableCheck();
            //Setting $skipSaleableCheck true as it is hardcoded false in the parent.
            $skipSaleableCheck=true;
            $allProducts = $this->getProduct()->getTypeInstance()->getUsedProducts($this->getProduct(), null);
            foreach ($allProducts as $product) {
                if ($product->isSaleable() || $skipSaleableCheck) {
                    $products[] = $product;
                }
            }
            $this->setAllowProducts($products);
        }
        return $this->getData('allow_products');
    }

}