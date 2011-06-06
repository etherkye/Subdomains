<?php

class dmPageRoute
{
  protected
  $slug,
  $page,
  $culture,
  $subdomain;
  
  public function __construct($slug, DmPage $page, $culture, $subdomain)
  {
    $this->slug     = $slug;
    $this->page     = $page;
    $this->culture  = $culture;
    $this->subdomain = $subdomain;
  }
  
  public function getSlug()
  {
    return $this->slug;
  }
  
  public function getPage()
  {
    return $this->page;
  }
  
  public function getCulture()
  {
    return $this->culture;
  }

  public function getSubdomain()
  {
    return $this->subdomain;
  }
}