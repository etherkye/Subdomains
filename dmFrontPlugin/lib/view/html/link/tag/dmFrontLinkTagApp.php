<?php

class dmFrontLinkTagApp extends dmFrontLinkTag
{
  protected
  $uri,
  $subdomain,
  $serviceContainer;

  public function __construct(dmFrontLinkResource $resource, dmFrontBaseServiceContainer $serviceContainer, array $requestContext, array $options = array())
  {
    $this->serviceContainer = $serviceContainer;

    parent::__construct($resource, $requestContext, $options);
  }

  protected function initialize(array $options = array())
  {
    parent::initialize($options);

    $subject = $this->resource->getSubject();
    $this->uri = $subject['uri'];
    $this->subdomain = $subject['subdomain'];
  }

  protected function getBaseHref()
  {
    $domain = $this->serviceContainer->getService('domain');

    return $domain->returnLink('', $this->uri, $this->subdomain);
  }
}