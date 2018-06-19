<?php

namespace OuterEdge\ConfigProduct\Helper;

class Data extends \Magento\ConfigurableProduct\Helper\Data
{

    /**
     * Get Options for Configurable Product Options
     *
     * Modified to add out of stock status option.
     *
     * @param \Magento\Catalog\Model\Product $currentProduct
     * @param array $allowedProducts
     * @return array
     */
    public function getOptions($currentProduct, $allowedProducts, $stockdata = null)
    {
        $options = [];
        $allowAttributes = $this->getAllowAttributes($currentProduct);

        foreach ($allowedProducts as $product) {
            $productId = $product->getId();
            foreach ($allowAttributes as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $productAttributeId = $productAttribute->getId();
                $attributeValue = $product->getData($productAttribute->getAttributeCode());

                $options[$productAttributeId][$attributeValue][] = $productId;
                $options['index'][$productId][$productAttributeId] = $attributeValue;
            }
            //Adding stock status in the option list.
            $options['stock'][$productId][] = $stockdata[$productId]['out_stock'];
        }
        return $options;
    }
}