<?php

class dmXmlSitemapGenerator extends dmConfigurable
{
  protected
  $dispatcher,
  $filesystem,
  $i18n,
  $serviceContainer,
  $notIn,
  $domain,
  $prefix;
  
  public function __construct(sfEventDispatcher $dispatcher, dmFilesystem $filesystem, dmI18n $i18n, dmAdminBaseServiceContainer $serviceContainer, array $options)
  {
    $this->dispatcher = $dispatcher;
    $this->filesystem = $filesystem;
    $this->i18n       = $i18n;
    $this->serviceContainer = $serviceContainer;
    
    $this->initialize($options);
  }

  protected function initialize(array $options) {
        $this->domain = $this->serviceContainer->getService('domain')->setDomain($this->getOption('domain'));
        $this->notIn = sfConfig::has('app_sitemap_not_in')?sfConfig::get('app_sitemap_not_in'):array();
        $this->prefix =  $this->serviceContainer->getService('script_name_resolver')->guessBootScriptFromWebDir('front','prod');
        $this->configure($options);
    }
  /*
   * Generates a sitemap
   * and save it in fullPath
   */
    public function execute() {
        $this->checkBaseUrl();


        if ($this->i18n->hasManyCultures()) {
            $this->write('sitemap.xml', $this->getCultureIndexXml($this->i18n->getCultures()));

            foreach ($this->i18n->getCultures() as $culture) {
                $subdomains = $this->domain->getSubdomains($culture);

                $this->write('sitemap_' . $culture . '.xml', $this->getSubdomainIndexXml($culture, $subdomains));
                foreach ($subdomains as $subdomain) {
                    $subdomain = $subdomain->get('Translation')->get($culture)->get('subdomain');
                    $this->write('sitemap_' . $culture . '_' . $subdomain . '.xml', $this->getSitemapXml($culture, $subdomain));
                }
            }
        } else {
            $culture = $this->i18n->getCulture();
            $subdomains = $this->domain->getSubdomains($culture);

            $this->write('sitemap.xml', $this->getSubdomainIndexXml($culture, $subdomains));
            foreach ($subdomains as $subdomain) {
                $subdomain = $subdomain->get('Translation')->get($culture)->get('subdomain');
                $this->write('sitemap_' . $culture . '_' . $subdomain . '.xml', $this->getSitemapXml($culture, $subdomain));
            }
        }

        $this->dispatcher->notify(new sfEvent($this, 'dm.sitemap.generated', array(
                    'dir' => $this->getOption('dir'),
                    'domain' => $this->getOption('domain'))
        ));
    }

  public function getDefaultOptions()
  {
    return array(
      'dir' => sfConfig::get('sf_web_dir')
    );
  }

  public function getFiles() {
        $files = array($this->getOption('dir') . '/sitemap.xml');

        if ($this->i18n->hasManyCultures()) {
            foreach ($this->i18n->getCultures() as $culture) {
                $subdomains = $this->domain->getSubdomains($culture);
                $files[] = $this->getOption('dir') . '/sitemap_'
                        . $culture
                        . '.xml';
                foreach ($subdomains as $subdomain) {
                    $files[] = $this->getOption('dir') . '/sitemap_'
                            . $culture . '_'
                            . $subdomain->get('Translation')->get($culture)->get('subdomain')
                            . '.xml';
                }
            }
        } else {
            $culture = $this->i18n->getCulture();
            $subdomains = $this->domain->getSubdomains($culture);
            foreach ($subdomains as $subdomain) {
                $files[] = $this->getOption('dir') . '/sitemap_'
                        . $culture . '_'
                        . $subdomain->get('Translation')->get($culture)->get('subdomain')
                        . '.xml';
            }
        }

        return $files;
    }

  public function delete()
  {
    $this->filesystem->unlink($this->getFiles());
  }
  
  protected function getCultureIndexXml(array $cultures) {
        return sprintf('<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
%s
</sitemapindex>
', $this->getCultureIndexSitemaps($cultures));
    }

    protected function getCultureIndexSitemaps(array $cultures) {
        $sitemaps = array();

        foreach ($cultures as $culture) {
            $sitemaps[] = sprintf('  <sitemap>
    <loc>%s</loc>
  </sitemap>',
                      $this->domain->returnLink("",'/sitemap_' . $culture . '.xml',"DEFAULT",true));
        }

        return implode("\n", $sitemaps);
    }

    protected function getSubdomainIndexXml($culture, $subdomains) {
        return sprintf('<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
%s
</sitemapindex>
', $this->getSubdomainIndexSitemaps($culture, $subdomains));
    }

    protected function getSubdomainIndexSitemaps($culture, $subdomains) {
        $sitemaps = array();

        foreach ($subdomains as $subdomain) {
            $sitemaps[] = sprintf('  <sitemap>
    <loc>%s</loc>
  </sitemap>',
          $this->domain->returnLink("",$this->getOption('domain') . '/sitemap_'
                            . $culture . '_'
                            . $subdomain->get('Translation')->get($culture)->get('subdomain')
                            . '.xml',"DEFAULT",true));
        }

        return implode("\n", $sitemaps);
    }

    protected function getSitemapXml($culture, $subdomain) {
        return sprintf('<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
%s
</urlset>',
                $this->getUrls($this->getPages($culture, $subdomain), $culture)
        );
    }
  
  /*
   * Wich pages should figure on sitemap ?
   * @return array of dmPage objects
   */
    protected function getPages($culture, $subdomain) {
        $query = dmDb::query('DmPage p')
                ->withI18n($culture)
                ->where('pTranslation.is_secure = ?', false)
                ->addWhere('pTranslation.is_active = ?', true)
                ->addWhere('p.module != ? OR ( p.action != ? AND p.action != ? AND p.action != ?)', array('main', 'error404', 'search', 'signin'));
                if(!empty($this->notIn)){
                    $query->andWhere('p.module NOT IN ? OR p.action = ?', array($this->notIn, 'list'));
                }
                $query->andWhere('pTranslation.subdomain LIKE ?', $subdomain)
                ->orderBy('p.lft asc');
        return $query->fetchRecords();
    }
  
   protected function getUrls(myDoctrineCollection $pages, $culture) {
        $urls = array();

        foreach ($pages as $page) {
            $url = $this->getUrl($page, $culture);
            if (!is_null($url)) {
                $urls[] = $url;
            }
        }

        return implode("\n", $urls);
    }

    protected function getUrl(dmPage $page, $culture) {
        $pageSlug = $page->get('Translation')->get($culture)->get('slug');
        $pageSubdomain = $page->get('Translation')->get($culture)->get('subdomain');

        if ($pageSubdomain == "DEFAULT") {
            $pageSubdomain = dmConfig::get('site_subdomain_default');
        }
        return sprintf('  <url>
    <loc>
      %s
    </loc>
  </url>', $this->domain->returnLink($this->prefix,$pageSlug,$pageSubdomain,true));
    }

  protected function write($filePath, $xml)
  {
    $file = dmOs::join($this->getOption('dir'), $filePath);
    
    if(!file_put_contents($file, $xml))
    {
      throw new dmException('Can not save xml sitemap to '.dmProject::unRootify($file));
    }

    @$this->filesystem->chmod($file, 0666);
  }
  
  public function getUpdatedAt($file)
  {
    $this->checkFileExists($file);

    return filemtime($file);
  }
  
  public function countUrls($file)
  {
    $this->checkFileExists($file);
    
    return substr_count(file_get_contents($file), '<loc>');
  }
  
  public function getFileSize($file)
  {
    $this->checkFileExists($file);
    
    return round(filesize($file) / 1024, 2).' KB';
  }
  
  public function getWebPath($file)
  {
    $this->checkBaseUrl();
    
    return $this->getOption('domain').str_replace(sfConfig::get('sf_web_dir'), '', $file);
  }
  
  protected function checkFileExists($file = null)
  {
    $file = $file ? $file : $this->getOption('dir').'/sitemap.xml';
    
    if (!file_exists($file))
    {
      throw new dmException(sprintf('The sitemap file does not exists'));
    }
  }
  
  protected function checkBaseUrl()
  {
    if (!$this->getOption('domain'))
    {
      throw new dmException('You must give a domain option like www.my-domain.com');
    }
  }
}

class dmSitemapNotWritableException extends dmException
{
  
}