<?php

class dmPageRouting extends dmConfigurable
{
  protected
  $serviceContainer;
  
  public function __construct(dmFrontBaseServiceContainer $serviceContainer, array $options = array())
  {
    $this->serviceContainer = $serviceContainer;
    
    $this->initialize($options);
  }
  
  protected function initialize(array $options)
  {
    $this->configure($options);
  }
  
  /*
   * @return $pageRoute instance of dmPageRoute, or false
   */
  public function find($slug, $culture = null)
  {
    $domain = $this->serviceContainer->getService('domain');

    $culture = null === $culture ? $this->serviceContainer->getParameter('user.culture') : $culture;
    $subdomain = $domain->getSubdomain();
      //throw new dmException(sprintf('Slug: %s, Culture: %s, Subdomain %s', $slug,$culture,$domain->getSubdomain()));
    if($page = $this->findPageForCulture($slug, $culture,$subdomain)){
    }else if($page = $this->findPageForWithoutCulture($slug,$subdomain)){
      $culture = $page->getCulture();
    }else{
      return false;
    }

    return $this->createRoute($slug, $page, $culture, $subdomain);
  }

  protected function findPageForCulture($slug, $culture,$subdomain = null)
  {
    return dmDb::table('DmPage')->findOneBySlug($slug, $culture,$subdomain);
  }

  protected function findPageForWithoutCulture($slug,$subdomain = null)
  {
    $i18n = $this->serviceContainer->getService('i18n');

    if (!$i18n->hasManyCultures())
    {
      return false;
    }

    // search in all cultures
    $page = dmDb::query('DmPage p')
    ->innerJoin('p.Translation t')
    ->where('t.slug = ?', $slug);
    if(!is_null($subdomain)){
        $page ->andWhere('t.subdomain = ? OR t.subdomain = ?',array($subdomain,dmConfig::get('site_subdomain_default')));
    }
    $page = $page->fetchOne();

    if (!$page)
    {
      return false;
    }

    return $page;
  }

  protected function createRoute($slug, DmPage $page, $culture, $subdomain)
  {
    return new dmPageRoute($slug, $page, $culture, $subdomain);
  }
}