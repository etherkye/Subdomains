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
    if(!$page = $this->findPageForCulture($slug, $culture,$subdomain))
    {
      if($page = $this->findPageForCulture($slug, $culture))
      {
          $subdomain = $page->getSubdomain();
      }else if($page = $this->findPageForSubdomain($slug,$subdomain)){
          $culture = $page->getCulture();


//        $result = $this->findPageAndCultureForAnotherCulture($slug,$domain->getSubdomain());
//
//        if (!$result)
//        {
//          return $this->findDefaultSubdomain($slug,$culture);
//        }
//
//        list($page, $culture) = $result;
      }
    }

    return $this->createRoute($slug, $page, $culture, $subdomain);
  }

  private function findDefaultSubdomain($slug, $culture){
    if(!$page = $this->findPageForCulture($slug, $culture))
    {
      $result = $this->findPageAndCultureForAnotherCulture($slug);

      if (!$result)
      {
        return false;
      }

      list($page, $culture) = $result;
    }

    return $this->createRoute($slug, $page, $culture, "DEFAULT");
  }

  protected function findPageForCulture($slug, $culture,$subdomain = null)
  {
    return dmDb::table('DmPage')->findOneBySlug($slug, $culture,$subdomain);
  }

   protected function findPageForSubdomain($slug, $subdomain = null)
  {
    return dmDb::table('DmPage')->findOneBySlug($slug, null, $subdomain);
  }

  protected function findPageAndCultureForAnotherCulture($slug,$subdomain = null)
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

    $culture = $page->getCulture();

    return array($page, $culture);
  }

  protected function createRoute($slug, DmPage $page, $culture, $subdomain)
  {
    return new dmPageRoute($slug, $page, $culture, $subdomain);
  }
}