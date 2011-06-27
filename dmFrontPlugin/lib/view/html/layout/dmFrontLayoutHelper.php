<?php

class dmFrontLayoutHelper extends dmCoreLayoutHelper
{
  protected
    $page;

  protected function initialize(array $options)
  {
    parent::initialize($options);

    if(!$currentPage = $this->serviceContainer->getParameter('context.page'))
    {
      throw new dmException('Can not use Diem layout helper because no Diem page is loaded.');
    }

    $this->setPage($currentPage);
  }
    
  public function setPage(DmPage $page)
  {
    $this->page = $page;
  }
  
  public function renderBodyTag($options = array())
  {
    $options = dmString::toArray($options);

    $options['class'] = dmArray::toHtmlCssClasses(array_merge(dmArray::get($options, 'class', array()), array(
      'page_'.$this->page->get('module').'_'.$this->page->get('action'),
      $this->page->getPageView()->getLayout()->get('css_class')
    )));
    
    return parent::renderBodyTag($options);
  }

  protected function getMetas()
  {
    $metas = array(
      'description'  => $this->page->get('description'),
      'title'        => dmConfig::get('title_prefix').$this->page->get('title').dmConfig::get('title_suffix')
    );

    if(!$this->isHTML5()){
      $metas['language'] = $this->serviceContainer->getParameter('user.culture');
    }
    
    if (sfConfig::get('dm_seo_use_keywords') && $keywords = $this->page->get('keywords'))
    {
      $metas['keywords'] = $keywords;
    }
    
    if (!dmConfig::get('site_indexable') || !$this->page->get('is_indexable'))
    {
      $metas['robots'] = 'noindex, nofollow';
    }
    
    if (dmConfig::get('gwt_key') && $this->page->getNode()->isRoot())
    {
      $metas['google-site-verification'] = dmConfig::get('gwt_key');
    }

    $metas = array_merge($metas, $this->getService('response')->getMetas());
    
    return $metas;
  }
  
  public function renderEditBars()
  {
    $user = $this->getService('user');
    
    if (!$user->can('admin'))
    {
      return '';
    }
    
    $helper = $this->getHelper();
    
    $cacheKey = sfConfig::get('sf_cache') ? $user->getCacheHash() : null;
    
    $html = '';
    
    if (sfConfig::get('dm_pageBar_enabled', true) && $user->can('page_bar_front'))
    {
      $html .= $helper->renderPartial('dmInterface', 'pageBar', array('cacheKey' => $cacheKey));
    }
    
    if (sfConfig::get('dm_mediaBar_enabled', true) && $user->can('media_bar_front'))
    {
      $html .= $helper->renderPartial('dmInterface', 'mediaBar', array('cacheKey' => $cacheKey));
    }
    
    if ($user->can('tool_bar_front'))
    {
      $html .= $helper->renderPartial('dmInterface', 'toolBar', array('cacheKey' => $cacheKey));
    }
    
    return $html;
  }

  public function getJavascriptConfig()
  {
    return array_merge(parent::getJavascriptConfig(), array(
      'page_id' => $this->page->get('id')
    ));
  }
  
  public function renderGoogleAnalytics()
  {
    if (($gaKey = dmConfig::get('ga_key')) && !$this->getService('user')->can('admin') && !dmOs::isLocalhost())
    {
      return $this->getGoogleAnalyticsCode($gaKey);
    }
    
    return '';
  }

  /**
   * Returns the script code to generate the google analytic async code.
   *
   * @param string $gaKey
   * @return string
   */
  protected function getGoogleAnalyticsCode($gaKey)
  {
      $domain = $this->getService('domain');
      $html =
     "<script type=\"text/javascript\">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', '".$gaKey."']);".
($domain->hasSubdomains()?"
   _gaq.push(['_setDomainName','.".$domain->getDomain()."']);
   _gaq.push(['_setAllowHash',false]);
  ":"").
  "_gaq.push(['_trackPageview']);

(function() {
  var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
  ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
  (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(ga);
})();
</script>";
      return $html;
  }

  public function renderStylesheets()
  {
    $settings = array_merge(
                      array('enabled' => false, 'number' => 2, 'name'=> 'cdn', 'start' => 1),
                      sfConfig::get('dm_cdn_default',array()),
                      sfConfig::get('dm_cdn_css',array())
                  );
    if(isset($settings['enabled']) && $settings['enabled']){
      /*
       * Allow listeners of dm.layout.filter_stylesheets event
       * to filter and modify the stylesheets list
       */
      $stylesheets = $this->dispatcher->filter(
      new sfEvent($this, 'dm.layout.filter_stylesheets'),
      $this->getService('response')->getStylesheets()
      )->getReturnValue();

      $relativeUrlRoot = dmArray::get($this->serviceContainer->getParameter('request.context'), 'relative_url_root');

      $domain = $this->serviceContainer->getService('domain');

      if(!isset($settings['list'])){
        $settings['list'] = array();
          for($i=0;$i<$settings['number'];$i++){
            $settings['list'][] = $settings['name'].($i+$settings['start']);
          }
      }
      $i = 0;

      $html = '';
      foreach ($stylesheets as $file => $options)
      {
          $stylesheetTag = '<link rel="stylesheet" type="text/css" media="'.dmArray::get($options, 'media', 'all').'" href="'.
                  ($file{0} === '/' ? $domain->returnLink('',$relativeUrlRoot.$file,$settings['list'][$i],true) : $file) .
                          '" />';
          $i = ($i+1)%count($settings['list']);
          if (isset($options['condition']))
          {
                  $stylesheetTag = sprintf('<!--[if %s]>%s<![endif]-->', $options['condition'], $stylesheetTag);
          }

          $html .= $stylesheetTag."\n";
      }

      sfConfig::set('symfony.asset.stylesheets_included', true);

      return $html;
    }else{
      return parent::renderStylesheets();
    }
  }

  protected function renderJavascriptsIncludes()
  {
    $settings = array_merge(
                  array('enabled' => false, 'number' => 2, 'name'=> 'cdn', 'start' => 1),
                  sfConfig::get('dm_cdn_default',array()),
                  sfConfig::get('dm_cdn_js',array())
              );
    if(isset($settings['enabled']) && $settings['enabled']){
      /*
       * Allow listeners of dm.layout.filter_javascripts event
       * to filter and modify the javascripts list
       */
      $javascripts = $this->dispatcher->filter(
      new sfEvent($this, 'dm.layout.filter_javascripts'),
      $this->serviceContainer->getService('response')->getJavascripts()
      )->getReturnValue();

      sfConfig::set('symfony.asset.javascripts_included', true);

      $relativeUrlRoot = dmArray::get($this->serviceContainer->getParameter('request.context'), 'relative_url_root');

      $domain = $this->serviceContainer->getService('domain');

      if(!isset($settings['list'])){
        $settings['list'] = array();
          for($i=0;$i<$settings['number'];$i++){
            $settings['list'][] = $settings['name'].($i+$settings['start']);
          }
      }
      $i = 0;

      $html = '';
      foreach ($javascripts as $file => $options)
      {
        if(empty($options['head_inclusion']))
        {
          $scriptTag = '<script type="text/javascript" src="'.
                  ($file{0} === '/' ? $domain->returnLink('',$relativeUrlRoot.$file,$settings['list'][$i],true) : $file) .
                          '"></script>';
          $i = ($i+1)%count($settings['list']);

          if (isset($options['condition'])) {
                  $scriptTag = sprintf('<!--[if %s]>%s<![endif]-->', $options['condition'], $scriptTag);
          }
          $html .= $scriptTag;
        }
      }

      return $html;
    }else{
      return parent::renderJavascriptsIncludes();
    }
  }

  public function renderFavicon()
  {
    $settings = array_merge(
                      array('enabled' => false, 'number' => 2, 'name'=> 'cdn', 'start' => 1),
                      sfConfig::get('dm_cdn_default',array()),
                      sfConfig::get('dm_cdn_image/x-icon',array()),
                      sfConfig::get('dm_cdn_ico',array())
                  );
    if(isset($settings['enabled']) && $settings['enabled']){
      $domain = $this->serviceContainer->getService('domain');
      $favicon = $this->getFavicon();

      if ($favicon)
      {
        return sprintf('<link rel="shortcut icon" href="%s" type="%s" />',
              $domain->returnLink('',$favicon,(isset($settings['list'])?$settings['list'][0]:($settings['name'].$settings['start'])),true),
              'image/x-icon'
              )."\n";
      }

      return '';
    }else{
      return parent::renderFavicon();
    }
  }

}
