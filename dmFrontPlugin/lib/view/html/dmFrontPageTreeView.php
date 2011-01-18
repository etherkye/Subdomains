<?php

class dmFrontPageTreeView extends dmPageTreeView
{

  protected function renderPageLink(array $page)
  {
    $pageSlug = $page[7];
    $pageSubdomain = $page[6];

    if($pageSubdomain == "DEFAULT"){
        $pageSubdomain = dmConfig::get('site_subdomain_default');
    }

    $subdomain = substr(str_replace(dmConfig::get('site_url'),'',$_SERVER['SERVER_NAME']),0,-1);

    if($pageSubdomain == $subdomain){
        $baseHref = $pageSlug;
    }else{
        $baseHref = "http://" .(!empty($pageSubdomain)?$pageSubdomain.".":""). dmConfig::get('site_url') . $this->getHrefPrefix().($pageSlug ? '/'.$pageSlug : '');
    }

    return '<a href="'.$baseHref.'" data-page-id="'.$page[0].'"><ins></ins>'.$page[5].'</a>';
  }

  public function getHrefPrefix()
  {
    return sfConfig::get('sf_no_script_name')
    ? ''
    : 'index.php';
  }

  protected function getRecordTreeQuery()
  {
    return parent::getRecordTreeQuery()->addSelect('pageTranslation.slug');
  }

}