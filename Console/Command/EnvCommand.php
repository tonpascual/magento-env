<?php

namespace Tonpascual\Env\Console\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Xml\Generator;
use Magento\Framework\Xml\Parser;
use Tonpascual\Env\Plugin\Framework\App\Dbenv;
use	Symfony\Component\Console\Command\Command;
use	Symfony\Component\Console\Input\InputInterface;
use	Symfony\Component\Console\Output\OutputInterface;

class EnvCommand extends Command
{
    const INPUT_KEY_EXTENDED = 'extended';
        
    private $moduleReader;    
    
    private $xmlParser;    
    
    private $xmlGenerator;    
    
    private $config;
    
    public function __construct(
        Reader                  $moduleReader,
        Parser                  $xmlParser,
        Generator               $xmlGenerator,    
        ScopeConfigInterface    $config,
        Dbenv                   $dbenv) 
    {
        $this->moduleReader = $moduleReader;
        $this->xmlParser    = $xmlParser;
        $this->xmlGenerator = $xmlGenerator;
        $this->config       = $config;
        $this->dbenvObj     = $dbenv;
        
        parent::__construct();
    }    
        
    protected function configure()
    {
        $this->setName('env:setup')
            ->setDescription('setup console db config');
        
        parent::configure();        
    }
    
    protected function execute(
        InputInterface	$input,	
        OutputInterface$output)
    {
        $output->writeln('Saving console credentials');
                
        $baseURL = $this->config->getValue('web/unsecure/base_url');
        if($this->config->getValue('web/secure/use_in_frontend'))
        {
            $baseURL = $this->config->getValue('web/secure/base_url');
        }
               
        $info = parse_url($baseURL);
        
        if(is_array($info)
            && isset($info['host'])
            && !empty($info['host']))
        {            
            $fileIterator = $this->moduleReader
                ->getConfigurationFiles('stages.xml');

            if($fileIterator->count() > 0)
            {
                $xmlDom = $this->xmlParser
                    ->loadXML($fileIterator->current())
                    ->getDom();

                $xpath = $this->dbenvObj->createXpathInstance($xmlDom);
                                
                $itemNodes = $xpath->query("//urls/item"); 
                foreach($itemNodes as $itemNode)
                {
                    $itemNode->removeAttribute('console');
                }                
                
                $nodes = $xpath->query("//urls/item[text()='{$info['host']}']");             
                if($nodes->length == 1)
                {
                    $item = $nodes->item(0);
                    $item->setAttribute('console', 'true');                    
                }
                else if($nodes->length == 0)
                {
                    $element = $xmlDom->createElement('item', $info['host']);
                    $element->setAttribute('console', 'true');
                    
                    $urlNodes = $xpath->query("//urls");
                    $parent = $urlNodes->item(0);
                    $parent->appendChild($element);
                }
                
                $path = $this->moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Ton_Test');
                
                $xmlDom->save($path . '/stages.xml');
            }
        }
    }
    
}
