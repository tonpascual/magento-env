<?php
namespace Tonpascual\Env\Plugin\Framework\App;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Config\FileIteratorFactory;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Xml\Parser;

class Dbenv
{
    const CONFIG_FILE_NAME = 'stages.xml';

    private $dirList;    
    
    private $driverPool;    
    
    private $fileIteratorFactory;
    
    private $xmlParser;    
    
    private $httpRequest;    
   
    public function __construct(
        DirectoryList       $dirList,
        DriverPool          $driverPool,
        FileIteratorFactory $fileIteratorFactory,
        Parser              $xmlParser,
        Http                $httpRequest) 
    {
        $this->dirList              = $dirList;
        $this->driverPool           = $driverPool;
        $this->fileIteratorFactory  = $fileIteratorFactory;        
        $this->xmlParser            = $xmlParser;
        $this->httpRequest          = $httpRequest;
    }    
    
    public function beforeCreate($interceptor, $config)
    {
        $path = $this->dirList->getPath(DirectoryList::CONFIG);
        $fileDriver = $this->driverPool->getDriver(DriverPool::FILE);
        
        $fileIterator = $this->fileIteratorFactory->create(
            [$path . '/' . self::CONFIG_FILE_NAME]
        );

        if($fileDriver->isExists($path . '/' . self::CONFIG_FILE_NAME))
        {
            $xmlDom = $this->xmlParser
                ->loadXML($fileIterator->current())
                ->getDom();
            $xpath = $this->createXpathInstance($xmlDom);
            
            $nodes;
            if(PHP_SAPI === 'cli') 
            {
                $nodes = $xpath->query("//urls/item[@console='true']");                  
            }
            else
            {
                $host = $this->httpRequest->getServer('HTTP_HOST');
                $nodes = $xpath->query("//urls/item[text()='{$host}']");                  
            }
            
            if($nodes
                && $nodes->length == 1)
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
