<?php
namespace TJM\Component\DependencyInjection\Loader;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/*
Class: MultiTypeLoader
Resolve config files from supported types for provided container at provided paths.
*/

class MultiTypeResolver implements LoaderResolverInterface{
	/*
	Method: __construct
	Arguments:
		container(ContainerBuilder): container to resolve configs for
		paths(String|Array): path(s) to resolve configs from
	*/
	public function __construct(ContainerBuilder $container, $paths){
		$this->container = $container;
		$this->locator = new FileLocator($paths);
	}

	/*
	Property: container
	Container we are loading configs for
	*/
	protected $container;

	/*
	Property: loaders
	Array of loaders to load configs with.
	*/
	protected $loaders = array();
	public function addLoader(LoaderInterface $loader){
		$this->loaders[] = $loader;
		$loader->setResolver($this);
		return $this;
	}
	public function createLoaderForType($type){
		$container = $this->container;
		$locater = $this->locator;
		switch($type){
			case 'yml':
				$loader = new YamlFileLoader($container, $locater);
			break;
			case 'php':
				$loader = new PhpFileLoader($container, $locater);
			break;
			case 'xml':
				$loader = new XmlFileLoader($container, $locater);
			break;
			case 'closure':
				$loader = new ClosureLoader($container, $locater);
			break;
			case 'ini':
				$loader = new IniFileLoader($container, $locater);
			break;
			default:
				$loader = false;
			break;
		}
		if($loader){
			$this->addLoader($loader);
		}
		return $loader;
	}
	public function getLoaders(){
		return $this->loaders;
	}

	/*
	Property: locator
	Locator to locate files for paths passed to constructor
	*/
	protected $locator;

	/*
	Method: resolve
	Returns a loader that can resolve the given resource, or false if it is unresolvable.
	*/
	public function resolve(mixed $resource, ?string $type = null): LoaderInterface|false{
		$resolvingLoader = false;
		foreach($this->loaders as $loader){
			if($loader->supports($resource, $type)){
				$resolvingLoader = $loader;
			}
		}
		if(!$resolvingLoader){
			if(!$type){
				$type = (is_callable($resource))
					? 'closure'
					: pathinfo($resource, PATHINFO_EXTENSION)
				;
			}
			$resolvingLoader = $this->createLoaderForType($type);
		}

		return $resolvingLoader;
	}
}
