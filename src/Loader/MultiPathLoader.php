<?php
namespace TJM\Component\DependencyInjection\Loader;

use Symfony\Component\Config\Exception\FileLoaderLoadException;
use Symfony\Component\Config\Loader\DelegatingLoader as Base;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/*
Class: MultiPathLoader
Load config files from supported types for provided container at provided paths.
*/

class MultiPathLoader extends Base{
	/*
	Method: __construct
	Arguments:
		container(ContainerInterface): container to load configs for
	*/
	public function __construct(ContainerBuilder $container){
		$this->container = $container;
	}

	/*
	Property: container
	Container to load configs for
	*/
	protected $container;

	/*
	Method: load
	Load a resource at a given absolute path, or relative to the current working directory of the PHP script.
	*/
	public function load(mixed $resource, ?string $type = null): mixed{
		$resolver = $this->getResolverForResource($resource, $type);
		if(is_string($resource) && $resource[0] === '/'){
			$resource = basename($resource);
		}
		$loader = $resolver->resolve($resource, $type);
		if($loader === false){
			throw new FileLoaderLoadException($resource);
		}
		return $loader->load($resource, $type);
	}

	/*
	Method: getResolver
	Get default resolver.  Create if not set
	*/
	public function getResolver(): LoaderResolverInterface{
		if(!isset($this->resolver)){
			$this->resolver = new MultiTypeResolver($this->container, getcwd());
		}
		return $this->resolver;
	}

	/*
	Method: getResolverForResource
	Get resolver for given resource
	*/
	public function getResolverForResource($resource, $type = null){
		if(is_string($resource) && $resource[0] === '/'){
			$dir = dirname($resource);
			$resolver = $this->getResolverForPath($dir);
		}else{
			$resolver = $this->getResolver();
		}
		return $resolver;
	}

	/*
	Property: resolvers
	Array of resolvers keyed by path they resolve for.
	*/
	protected $resolvers = Array();
	public function getResolverForPath($path){
		if(!is_dir($path)){
			$path = dirname($path);
		}
		if(!isset($this->resolvers[$path])){
			$this->resolvers[$path] = new MultiTypeResolver($this->container, $path);
		}
		return $this->resolvers[$path];
	}

	/*
	Method: supports
	{@inheritdoc}
	*/
	public function supports(mixed $resource, ?string $type = null): bool{
		$resolver = $this->getResolverForResource($resource, $type);
		return $resolver->resolve($resource, $type) !== false;
	}
}
