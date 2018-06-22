<?php

namespace OuterEdge\ConfigProduct\Block\Product\View\Type;

use Magento\Swatches\Block\Product\Renderer\Configurable as SwatchConfigurable;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Json\DecoderInterface;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\Catalog\Helper\Product as CatalogProduct;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\SwatchAttributesProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\Format;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;

/**
 * Class Configurable
 * @package OuterEdge\ConfigProduct\Block\Product\View\Type
 */
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
     * @var jsonDecoder interface
     */
    protected $jsonDecoder;

    /**
     * @var StockRepository
     */
    protected $_stockRepository;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var StockItemInterfaceFactory
     */
    private $stockItemInterfaceFactory;

    /**
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param EncoderInterface $jsonEncoder
     * @param DecoderInterface $jsonDecoder
     * @param Data $helper
     * @param CatalogProduct $catalogProduct
     * @param CurrentCustomer $currentCustomer
     * @param PriceCurrencyInterface $priceCurrency
     * @param ConfigurableAttributeData $configurableAttributeData
     * @param SwatchData $swatchHelper
     * @param Media $swatchMediaHelper
     * @param array $data other data
     * @param SwatchAttributesProvider $swatchAttributesProvider
     * @param Format|null $localeFormat
     * @param StockItemInterfaceFactory $stockItemInterfaceFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder,
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
        $this->jsonDecoder =$jsonDecoder;

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
        $this->context = $context;
        $this->stockItemInterfaceFactory = $stockItemInterfaceFactory;
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
     * Extend the configuration for js to add stock values.
     *
     * @return string
     */
    public function getJsonConfig()
    {
        $config = $this->jsonDecoder->decode(parent::getJsonConfig());
        $currentProduct = $this->getProduct();
        $options = $this->helper->getOptions($currentProduct, $this->getAllowProducts(),$this->getProductStock());
        //Adding stock details to the config product options.
        $config['stock']=isset($options['stock']) ? $options['stock'] : [];
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