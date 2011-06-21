<?php

abstract class dmMediaTag extends dmHtmlTag
{
  protected
  $resource,
  $context,
  $serviceContainer,
  $stylesheets = array(),
  $javascripts = array();

  public function __construct(dmMediaResource $resource, dmContext $context, dmBaseServiceContainer $serviceContainer, array $options = array())
  {
    $this->resource         = $resource;
    $this->context          = $context;
    $this->serviceContainer = $serviceContainer;
    
    $this->initialize($options);
  }
  
  public function width($v)
  {
    return $this->setOption('width', max(0, (int)$v));
  }

  public function height($v)
  {
    return $this->setOption('height', max(0, (int)$v));
  }

  public function size($width, $height = null)
  {
    if (is_array($width))
    {
      list($width, $height) = $width;
    }

    return $this->width($width)->height($height);
  }

  public function getSrc($throwException = true)
  {
    if ($throwException)
    {
      return dmArray::get($this->prepareAttributesForHtml($this->options), 'src');
    }
    else
    {
      try
      {
        return dmArray::get($this->prepareAttributesForHtml($this->options), 'src');
      }
      catch(Exception $e)
      {
        return false;
      }
    }
  }

  public function getAbsoluteSrc($throwException = true)
  {
    $src = $this->getSrc($throwException);

    $uriPrefix = $this->context->getRequest()->getUriPrefix();

    if (strpos($src, $uriPrefix) !== 0)
    {
      $src = $uriPrefix.$src;
    }

    return $src;
  }

  public function getWidth()
  {
    return dmArray::get($this->prepareAttributesForHtml($this->options), 'width');
  }

  public function getHeight()
  {
    return dmArray::get($this->prepareAttributesForHtml($this->options), 'width');
  }

  protected function prepareAttributesForHtml(array $attributes)
  {
    $attributes = parent::prepareAttributesForHtml($attributes);

    $attributes['src'] = $this->resource->getWebPath();

    return $attributes;
  }

  protected function convertAttributesToHtml(array $attributes){

    $media = $this->resource->getSource();

    if ($media instanceof DmMedia && isset($attributes['src']))
    {
      $domain = $this->serviceContainer->getService('domain');
      $settings = array_merge(
                      array('enabled' => false, 'number' => 2, 'name'=> 'cdn', 'start' => 1),
                      sfConfig::get('dm_cdn_default',array()),
                      sfConfig::get('dm_cdn_'.$media->get('mime'),array())
                  );

      if(isset($settings['enabled']) && $settings['enabled']){
        if(isset($settings['list']) && isset($settings['list'][$media->get('cdn')])){
            $cdn = $settings['list'][$media->get('cdn')];
        }else{
            $cdn = $settings['name'].($media->get('cdn') + $settings['start']);
        }  
        $attributes['src'] = $domain->returnLink('',$attributes['src'],$cdn,true);
      }
    }

    $htmlAttributesString = parent::convertAttributesToHtml($attributes);
    return $htmlAttributesString;
  }

  protected function hasSize()
  {
    return !(empty($this->options['width']) && empty($this->options['height']));
  }

  public function quality($val)
  {
    // override me
  }

  protected function addJavascript($keys)
  {
    $this->javascripts = array_merge($this->javascripts, (array) $keys);
  }

  public function getJavascripts()
  {
    return $this->javascripts;
  }

  protected function addStylesheet($keys)
  {
    $this->stylesheets = array_merge($this->stylesheets, (array) $keys);
  }

  public function getStylesheets()
  {
    return $this->stylesheets;
  }
}