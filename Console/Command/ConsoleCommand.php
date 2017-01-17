<?php

namespace Tonpascual\Env\Console\Command;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\FileIteratorFactory;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Xml\Parser;
use	Symfony\Component\Console\Command\Command;
use	Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use	Symfony\Component\Console\Output\OutputInterface;
use	Tonpascual\Env\Plugin\Framework\App\Dbenv;

class ConsoleCommand extends Command
{
    const INPUT_KEY_ENV = 'stage';
        
    private $dirList;    
    
    private $driverPool;    
    
    private $fileIteratorFactory;    
    
    private $xmlParser;    
    
    private $dbenv;
    
    public function __construct(
        DirectoryList           $dirList,
        DriverPool              $driverPool,
        FileIteratorFactory     $fileIteratorFactory,
        Parser                  $xmlParser,
        Dbenv                   $dbenv) 
    {
        $this->dirList              = $dirList;
        $this->driverPool           = $driverPool;
        $this->fileIteratorFactory  = $fileIteratorFactory;                
        $this->xmlParser            = $xmlParser;
        $this->dbenv                = $dbenv;
        
        parent::__construct();
    }    
        
    protected function configure()
    {
        $options = [
            new InputArgument(
                self::INPUT_KEY_ENV,
                InputArgument::REQUIRED,
                'Sets the console db credentials.'
            )
        ];        

        $this->setName('env:console')
            ->setDescription('Setup console db config')
            ->setDefinition($options);
        
        parent::configure();        
    }
    
    protected function execute(
        InputInterface	$input,	
        OutputInterface$output)
    {
        $path   = $this->dirList->getPath(DirectoryList::CONFIG);        
        $stage  = $input->getArgument(self::INPUT_KEY_ENV);        
        
        $fileIterator = $this->fileIteratorFactory->create(
            [$path . '/' . Dbenv::CONFIG_FILE_NAME]
        );
                
        if($fileIterator->count() > 0)
        {
            $xmlDom = $this->xmlParser
                ->loadXML($fileIterator->current())
                ->getDom();

            $xpath = $this->dbenv->createXpathInstance($xmlDom);

            $itemNodes = $xpath->query("//urls/item"); 
            foreach($itemNodes as $itemNode)
            {
                $itemNode->removeAttribute('console');
            }                

            $nodes = $xpath->query("//urls/item[@type='$stage']");             
            if($nodes->length == 1)
            {
                $item = $nodes->item(0);
                $item->setAttribute('console', 'true');                    
            }
            else if($nodes->length == 0)
            {
                $output->writeln('Stage not in config');
                return;
                /*$element = $xmlDom->createElement('item', $stage);
                $element->setAttribute('console', 'true');

                $urlNodes = $xpath->query("//urls");
                $parent = $urlNodes->item(0);
                $parent->appendChild($element);*/
            }

            $output->writeln('Saving console credentials');
            $xmlDom->save($path . '/' . Dbenv::CONFIG_FILE_NAME);
        }
    }
    
}
