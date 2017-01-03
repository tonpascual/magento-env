<?php
namespace Tonpascual\Env\Plugin\Framework\App;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Xml\Parser;


class Dbenv
{
    private $moduleReader;    
    
    private $xmlParser;    
    
    private $httpRequest;    
   
    public function __construct(
        Reader  $moduleReader,
        Parser  $xmlParser,
        Http    $httpRequest) 
    {
        $this->moduleReader = $moduleReader;
        $this->xmlParser    = $xmlParser;
        $this->httpRequest  = $httpRequest;
    }    
    
    public function beforeCreate($interceptor, $config)
    {
        $fileIterator = $this->moduleReader
            ->getConfigurationFiles('stages.xml');
                
        if($fileIterator->count() > 0)
        {
            $xmlDom = $this->xmlParser
                ->loadXML($fileIterator->current())
                ->getDom();

            $host = $this->httpRequest->getServer('HTTP_HOST');
            $xpath = $this->createXpathInstance($xmlDom);
            $nodes = $xpath->query("//urls/item[text()='{$host}']");  
            
            if($nodes->length == 1)
            {
                $type = $nodes->item(0)
                    ->getAttribute('type');
                $newConfig = [];                
                $newConfig['host']      = $this->getXpathNodeValue($xpath, "/config/$type/db/host");
                $newConfig['dbname']    = $this->getXpathNodeValue($xpath, "/config/$type/db/dbname");
                $newConfig['username']  = $this->getXpathNodeValue($xpath, "/config/$type/db/username");
                $newConfig['password']  = $this->getXpathNodeValue($xpath, "/config/$type/db/password");
                
                $config = array_merge($config, $newConfig);                
            }
        }
        
        return [$config];
    }
    
    public function createXpathInstance(\DOMDocument $doc)
    {
        return new \DOMXPath($doc);
        
    }
    
    public function getXpathNodeValue(\DOMXPath $xpath, $query)
    {
        return $xpath
            ->query($query)
            ->item(0)
            ->nodeValue;
    }
}
