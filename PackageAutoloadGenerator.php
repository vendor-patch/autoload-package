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
	   
	   \Frdlweb\Contract\Autoload\ClassmapGeneratorInterface
public function addDirectory(string $dir); 

\Frdlweb\Contract\Autoload\Psr4GeneratorInterface
public function  $this->Psr4Generator->addNamespace($prefix, $resourceOrLocation, $prepend = false);


*/

class PackageAutoloadGenerator implements LoaderInterface
{
   use Nette\SmartObject;
   
    protected $packageDirectories = [];
    protected $ClassmapGenerator = null;
    protected $Psr4Generator = null; 
    protected $RemoteAutoloader = null;
    protected $allowRedundancy = false;
    protected $_toRegister = [];
	
	
   public function __construct( $allowRedundancy = false, ClassmapGeneratorInterface $ClassmapGenerator = null,
			       Psr4GeneratorInterface $Psr4Generator = null,
			       LoaderInterface $RemoteAutoloader = null 
	){
	   $this->ClassmapGenerator = (null !== $ClassmapGenerator) ? $ClassmapGenerator : new \Webfan\Autoload\CodebaseLoader;
	   $this->Psr4Generator = (null !== $Psr4Generator) ? $Psr4Generator : new \Webfan\Autoload\LocalPsr4Autoloader;
	   $this->RemoteAutoloader = (null !== $RemoteAutoloader) ? $RemoteAutoloader : null; // @Lacks Interface! new \Webfan\Autoload\RemoteFallbackLoader;
	   $this->allowRedundancy=$allowRedundancy;
   }
	
   public function withRedundancy(bool $allowRedundancy = false ){
        $this->allowRedundancy=$allowRedundancy;
     return $this;
   }	
	
   public function addDirectory(string $dir){
       return $this->_load($dir);	
   }
   public function load(string $dir){
       return $this->_load($dir);	
   }
    public function withDirectory($dir){	
	return $this->_load($dir);
    }
	
    protected function _withDirectoryClassmap($dir){		
	$this->ClassmapGenerator->addDirectory($dir);
        return $this;
    }
    public function loadClassmaps($classMaps){
            if (!is_array($classMaps)) {
                $classMaps =[$classMaps];
            }	    
	    foreach($classMaps as $path){
		 if(file_exists($path) && is_file($path)){
		    $path = dirname($path);	 
		 }
		$this->_withDirectoryClassmap($path);
	    }
	  return $this;
    }
    protected function _getComposerFile($dir)
    {
	    if(!isset($this->packageDirectories[$dir])){
		    $this->packageDirectories[$dir]=[];
	    }
	    try{    
		    $this->packageDirectories[$dir]['json'] = json_decode(file_get_contents($dir."/composer.json"), 1);
	    }catch(\Exception $e){
		    trigger_error(sprintf('% %', $e->getMessage() , __METHOD__), \E_USER_WARNING);
		    return [];
	    }

	    

	    return  $this->packageDirectories[$dir]['json'];	    
    }

  	
	
	
	
    protected function _load($dir)
    {
        $this->dir = $dir;
        $composer = $this->_getComposerFile($dir);
	    
        if(isset($composer["autoload"]["classmap"])){
            $this->loadClassmaps($composer["autoload"]["classmap"]);
        }
	    
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
            $fullpath = //$this->dir."/".
		    $file;
            if(file_exists($fullpath)){
                include $fullpath;
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

    public function loadPSR($namespaces, $psr4, $prepend = false)
    {
      //  $dir = $this->dir;
        // Foreach namespace specified in the composer, load the given classes
        foreach ($namespaces as $namespace => $classpaths) {
            if (!is_array($classpaths)) {
                $classpaths = [$classpaths];
            }
                    if ($psr4) {
			        foreach ($classpaths as $_classpath) {
				   $this->Psr4Generator->addNamespace($namespace, $_classpath, $prepend);					
				}
			 
		    }
	    	
		
          //  spl_autoload_register(
	$this->_toRegister[] =( function ($classname) use ($namespace, $classpaths, $dir, $psr4) {
                // Check if the namespace matches the class we are looking for
                if (preg_match("#^".preg_quote($namespace)."#", $classname)) {
                    // Remove the namespace from the file path since it's psr4
                    if ($psr4) {
                        $classname = str_replace($namespace, "", $classname);
                    }
                    $filename = preg_replace("#\\\\#", "/", $classname).".php";
                    foreach ($classpaths as $classpath) {
                        $fullpath = $classpath."/$filename";
                        if (file_exists($fullpath)) {
                            include $fullpath;
                        }
                    }
                }
            }
	   )
	   ;
        }
    }
 
public function register(bool $prepend = false){
		$this->ClassmapGenerator->register($prepend);
		$this->Psr4Generator->register($prepend);
		if(null !== $this->RemoteAutoloader){
			$this->RemoteAutoloader->register($prepend);
		}
	
}
	
}
