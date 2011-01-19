<?php

class dmFrontPageTreeView extends dmPageTreeView
{
  protected
  $serviceContainer;

  public function __construct(dmHelper $helper, dmFrontBaseServiceContainer $serviceContainer, $culture, array $options)
  {
    $this->serviceContainer   = $serviceContainer;
    parent::__construct($helper,$culture,$options);
  }

  protected function renderPageLink(array $page)
  {
      
    $pageSlug = $page[7];
    $pageSubdomain = $page[6];

    $domain = $this->serviceContainer->getService('domain');

    return '<a href="'.$domain->returnLink($this->getHrefPrefix(), $pageSlug, $pageSubdomain).'" data-page-id="'.$page[0].'"><ins></ins>'.$page[5].'</a>';
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