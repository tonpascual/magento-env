<?php
namespace Ton\Env\Plugin\Catalog;

use Magento\Catalog\Model\Product;

class ProductAround
{
    public function aroundGetName($interceptedInput)
    {
        $test = $interceptedInput->getData('name');
        
        //$test = $interceptedInput->getName();
        
        return	"Name of product";
    }
}

