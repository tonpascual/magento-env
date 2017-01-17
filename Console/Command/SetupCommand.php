<?php

namespace Tonpascual\Env\Console\Command;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Xml\Parser;
use	Symfony\Component\Console\Command\Command;
use	Symfony\Component\Console\Input\InputInterface;
use	Symfony\Component\Console\Output\OutputInterface;
use	Tonpascual\Env\Plugin\Framework\App\Dbenv;

class SetupCommand extends Command
{        
    private $dirList;    
    
    private $driverPool;    
    
    private $xmlParser;    
    
    private $moduleReader;        
    
    public function __construct(
        DirectoryList   $dirList,
        DriverPool      $driverPool,
        Reader          $moduleReader,
        Parser          $xmlParser) 
    {
        $this->dirList          = $dirList;
        $this->driverPool       = $driverPool;
        $this->moduleReader     = $moduleReader;
        $this->xmlParser        = $xmlParser;
        
        parent::__construct();
    }    
        
    protected function configure()
    {
        $this->setName('env:setup')
            ->setDescription('Setup config file');
        
        parent::configure();        
    }
    
    protected function execute(
        InputInterface	$input,	
        OutputInterface$output)
    {
        $configPath = $this->dirList->getPath(DirectoryList::CONFIG);
        $fileDriver = $this->driverPool->getDriver(DriverPool::FILE);
        
        if($fileDriver->isExists($configPath . '/' . Dbenv::CONFIG_FILE_NAME))
        {
            $output->writeln('Config file already exist');
            return;
        }                

        $output->writeln('Setup env stages config file');
        
        $fileIterator = $this->moduleReader
            ->getConfigurationFiles('stages.xml.sample');

        if($fileIterator->count() > 0)
        {
            $xmlDom = $this->xmlParser
                ->loadXML($fileIterator->current())
                ->getDom();

            $xmlDom->save($configPath . '/' . Dbenv::CONFIG_FILE_NAME);
        }
    }
    
}
