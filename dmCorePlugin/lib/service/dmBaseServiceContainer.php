<?php

abstract class dmBaseServiceContainer extends sfServiceContainer
{
  protected
  $options = array();
  
  public function configure(array $dependencies, array $options = array())
  {
    $this->options = array_merge($this->options, $options);
    
    $this->loadDependencies($dependencies);
    
    $this->loadParameters();
    
    $this->configureServices();
  }
  
  protected function loadDependencies(array $dependencies)
  {
    $this->setService('dispatcher',       $dependencies['context']->getEventDispatcher());
    $this->setService('user',             $dependencies['context']->getUser());
    $this->setService('response',         $dependencies['context']->getResponse());
    $this->setService('i18n',             $dependencies['context']->getI18n());
    $this->setService('logger',           $dependencies['context']->getLogger());
    $this->setService('config_cache',     $dependencies['context']->getConfigCache());
    $this->setService('controller',       $dependencies['context']->getController());
    $this->setService('request',          $dependencies['context']->getRequest());
    $this->setService('module_manager',   $dependencies['context']->getModuleManager());
    $this->setService('context',          $dependencies['context']);
    $this->setService('doctrine_manager', $dependencies['doctrine_manager']);
  }
  
  protected function loadParameters()
  {
    $this->setParameter('request.context',  $this->getService('request')->getRequestContext());
    
    $this->setParameter('user.culture',     $this->getService('user')->getCulture());
  }
  
  protected function configureServices()
  {
    $this->configureUser();
    
    if ($this->getService('response')->isHtmlForHuman())
    {
      $this->configureResponse();
      
      $this->configureAssetCompressor();
    }
  }
  
  protected function configureUser()
  {
    $this->getService('user')->setBrowser($this->getService('browser'));
  }
  
  protected function configureResponse()
  {
    /*
     * Response require asset aliases
     */
    $this->getService('response')->setAssetAliases(include($this->getService('config_cache')->checkConfig('config/dm/assets.yml')));
    
    /*
     * Response require cdn configuration
     */
    $this->getService('response')->setCdnConfig(array(
      'css' => sfConfig::get('dm_css_cdn',  array('enabled' => false)),
      'js'  => sfConfig::get('dm_js_cdn',   array('enabled' => false))
    ));
    
    /*
     * Response require asset configuration
     */
    $this->getService('response')->setAssetConfig($this->getService('asset_config'));
  }
  
  protected function configureAssetCompressor()
  {
    if (!sfConfig::get('dm_debug'))
    {
      $userCanCodeEditor = $this->getService('user')->can('code_editor');
      
      /*
       * Enable stylesheet compression
       */
      $stylesheetCompressor = $this->getService('stylesheet_compressor');
      $stylesheetCompressor->setOption('protect_user_assets', $userCanCodeEditor);
      $stylesheetCompressor->connect();

      /*
       * Enable javascript compression
       */
      $javascriptCompressor = $this->getService('javascript_compressor');
      $javascriptCompressor->setOption('protect_user_assets', $userCanCodeEditor);
      $javascriptCompressor->connect();
    }
  }
  
  public function connect()
  {
    $dispatcher = $this->getService('dispatcher');
    
    $dispatcher->connect('user.change_culture', array($this, 'listenToChangeCultureEvent'));
    
    $dispatcher->connect('user.change_theme', array($this, 'listenToChangeThemeEvent'));
    
    $dispatcher->connect('controller.change_action', array($this, 'listenToChangeActionEvent'));
    
    $this->connectServices();
  }
  
  protected function connectServices()
  {
    if (!dmConfig::isCli())
    {
      /*
       * Connect the tree watcher to make it aware of database modifications
       */
      $this->getService('page_tree_watcher')->connect();
      
      /*
       * Connect the cache cleaner
       */
      $this->getService('cache_cleaner')->connect();
    }
    
    if ('test' != sfConfig::get('sf_environment'))
    {
      /*
       * Connect the error watcher to make it aware of thrown exceptions
       */
      $this->getService('error_watcher')->connect();
      
      /*
       * Connect the event log to make it aware of database modifications
       */
      $this->getService('event_log')->connect();
      
      /*
       * Connect the request log to make it aware of controller end
       */
      $this->getService('request_log')->connect();
    }
    
    $this->getService('user')->connect();
  }

  /**
   * Listens to the user.change_culture event.
   *
   * @param sfEvent An sfEvent instance
   */
  public function listenToChangeCultureEvent(sfEvent $event)
  {
    $this->setParameter('user.culture', $event['culture']);
  }
  
  /**
   * Listens to the user.change_theme event.
   *
   * @param sfEvent An sfEvent instance
   */
  public function listenToChangeThemeEvent(sfEvent $event)
  {
    $this->setParameter('user.theme', $event['theme']);
  }
  /**
   * Listens to the controller.change_action event.
   *
   * @param sfEvent An sfEvent instance
   */
  public function listenToChangeActionEvent(sfEvent $event)
  {
    $this->setParameter('controller.module', $event['module']);
    $this->setParameter('controller.action', $event['action']);
  }
  
  /*
   * @return dmMediaResource
   */
  public function getMediaResource($source)
  {
    $resource = $this->getService('media_resource');
    $resource->initialize($source);
    
    return $resource;
  }
  
  /*
   * @return dmMediaTag
   */
  public function getMediaTag($resource)
  {
    if (!$resource instanceof dmMediaResource)
    {
      $resource = $this->getMediaResource($resource);
    }
    
    $this->setParameter('media_tag.class', $this->getParameter('media_tag_'.$resource->getMime().'.class'));
    $this->setParameter('media_tag.source', $resource);
    
    return $this->getService('media_tag');
  }
  
  /*
   * @return dmLinkResource
   */
  public function getLinkResource($source)
  {
    $resource = $this->getService('link_resource');
    $resource->initialize($source);
    
    return $resource;
  }
  
  
  /*
   * Compatibility with sfContext
   */
  public function get($name)
  {
    return $this->getService($name);
  }
  
  /**
   * Merges a service container parameter.
   *
   * @param string $name       The parameter name
   * @param mixed  $parameters The parameter value
   */
  public function mergeParameter($name, $value)
  {
    $this->parameters[strtolower($name)] = array_merge($this->parameters[strtolower($name)], $value);
  }
  
  
  public function reload($id)
  {
    if (!$this->hasService($id))
    {
      throw new InvalidArgumentException(sprintf('The service "%s" does not exist.', $id));
    }
    
    if(isset($this->shared[$id]))
    {
      unset($this->shared[$id]);
    }
  }
  
  /**
   * Returns true if the given service is defined.
   *
   * @param  string  $id      The service identifier
   *
   * @return Boolean true if the service is defined, false otherwise
   */
  public function hasService($id)
  {
    return isset($this->services[$id]) || (!empty($id) && method_exists($this, 'get'.dmString::camelize($id).'Service'));
  }

  /**
   * Gets a service.
   *
   * If a service is both defined through a setService() method and
   * with a set*Service() method, the former has always precedence.
   *
   * @param  string $id The service identifier
   *
   * @return object The associated service
   *
   * @throw InvalidArgumentException if the service is not defined
   */
  public function getService($id)
  {
    if (isset($this->services[$id]))
    {
      return $this->services[$id];
    }
    
    if (!empty($id) && method_exists($this, $method = 'get'.dmString::camelize($id).'Service'))
    {
      return $this->$method();
    }

    throw new InvalidArgumentException(sprintf('The service "%s" does not exist.', $id));
  }
  
}