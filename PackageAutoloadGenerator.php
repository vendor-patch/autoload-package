<?php

namespace Webfan\Autoload;
use Frdlweb\Contract\Autoload\LoaderInterface;
use Frdlweb\Contract\Autoload\ClassLoaderInterface;
use Frdlweb\Contract\Autoload\Psr4GeneratorInterface;
use Frdlweb\Contract\Autoload\ClassmapGeneratorInterface;
use Frdlweb\Contract\Autoload\GeneratorInterface;
use Frdlweb\Contract\Autoload\ResolverInterface;
use Frdlweb\Contract\Autoload\ConditionalResolverInterface;
use Frdlweb\Contract\Autoload\ContextInterface;
use Nette;
use SplFileInfo;
use Webfan\Traits\WithContextDirectories as DirectoriesTrait;
use Webfan\Traits\WithTimeout;


/** 

use Frdlweb\Contract\Autoload;
 

	interface ClassmapGeneratorInterface {				
		public function addDirectory(string $dir); 
	}	
	




use Frdlweb\Contract\Autoload\GeneratorInterface; 

	   public function withDirectory($dir); 
	   public function withAlias(string $alias, string $rewrite); 
	   public function withClassmap(array $classMap = null); 
	   public function withNamespace($prefix, $server, $prepend = false);
*/

class PackageAutoloadGeneratorimplements implements
	LoaderInterface, 
	ClassLoaderInterface, 
	ClassmapGeneratorInterface,
	ResolverInterface,
	ConditionalResolverInterface  // Interface: public function withContext(ContextInterface $Context); 
{
	use Nette\SmartObject,
      	DirectoriesTrait, 
	    WithTimeout,
	    ConditionalAutoloadNegotiationTrait  //Implements public function withContext(Context $Context)
		;
	
{
    
    protected $dir;

    public function getComposerFile()
    {
        return json_decode(file_get_contents($this->dir."/composer.json"), 1);
    }

    public function load($dir)
    {
        $this->dir = $dir;
        $composer = $this->getComposerFile();
        if(isset($composer["autoload"]["psr-4"])){
            $this->loadPSR4($composer['autoload']['psr-4']);
        }
        if(isset($composer["autoload"]["psr-0"])){
            $this->loadPSR0($composer['autoload']['psr-0']);
        }
        if(isset($composer["autoload"]["files"])){
            $this->loadFiles($composer["autoload"]["files"]);
        }
    }
    
    public function loadFiles($files){
        foreach($files as $file){
            $fullpath = $this->dir."/".$file;
            if(file_exists($fullpath)){
                include_once($fullpath);
            }
        }
    }

    public function loadPSR4($namespaces)
    {
        $this->loadPSR($namespaces, true);
    }

    public function loadPSR0($namespaces)
    {
        $this->loadPSR($namespaces, false);
    }

    public function loadPSR($namespaces, $psr4)
    {
        $dir = $this->dir;
        // Foreach namespace specified in the composer, load the given classes
        foreach ($namespaces as $namespace => $classpaths) {
            if (!is_array($classpaths)) {
                $classpaths = array($classpaths);
            }
            spl_autoload_register(function ($classname) use ($namespace, $classpaths, $dir, $psr4) {
                // Check if the namespace matches the class we are looking for
                if (preg_match("#^".preg_quote($namespace)."#", $classname)) {
                    // Remove the namespace from the file path since it's psr4
                    if ($psr4) {
                        $classname = str_replace($namespace, "", $classname);
                    }
                    $filename = preg_replace("#\\\\#", "/", $classname).".php";
                    foreach ($classpaths as $classpath) {
                        $fullpath = $this->dir."/".$classpath."/$filename";
                        if (file_exists($fullpath)) {
                            include_once $fullpath;
                        }
                    }
                }
            });
        }
    }
}
