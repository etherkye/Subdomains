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
    $culture = null === $culture ? $this->serviceContainer->getParameter('user.culture') : $culture;

    $subdomain = substr(str_replace(dmConfig::get('site_url'),'',$_SERVER['SERVER_NAME']),0,-1);
    if($subdomain == dmConfig::get('site_subdomain_default')){
        $subdomain = "DEFAULT";
    }

    if(!$page = $this->findPageForCulture($slug, $culture,$subdomain))
    {
      $result = $this->findPageAndCultureForAnotherCulture($slug,$subdomain);

      if (!$result)
      {
        return false;
      }

      list($page, $culture) = $result;
    }

    return $this->createRoute($slug, $page, $culture);
  }

  protected function findPageForCulture($slug, $culture,$subdomain = null)
  {
    return dmDb::table('DmPage')->findOneBySlug($slug, $culture,$subdomain);
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
        $page ->andWhere('t.subdomain = ?',$subdomain);
    }
    $page->fetchOne();

    if (!$page)
    {
      return false;
    }

    // use the default culture
    if ($page->get('Translation')->contains(sfConfig::get('sf_default_culture')))
    {
      $culture = sfConfig::get('sf_default_culture');
    }
    // if not exists, use the first culture...
    else
    {
      $culture = dmArray::first(array_keys($page->get('Translation')->getData()));
    }

    return array($page, $culture);
  }

  protected function createRoute($slug, DmPage $page, $culture)
  {
    return new dmPageRoute($slug, $page, $culture);
  }
}