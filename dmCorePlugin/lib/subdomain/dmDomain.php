<?php

class dmDomain extends dmConfigurable {

    protected
    $domain,
    $subdomain;

    public function __construct(array $options = array()) {
        $this->initialize($options);
    }

    protected function initialize(array $options = array()) {
        $this->domain = dmConfig::get('site_url');
        if (empty($this->domain)) {
            $this->domain = $this->generateDomain($_SERVER['SERVER_NAME']);
        }
        $this->subdomain = $this->generateSubDomain($_SERVER['SERVER_NAME'], $this->domain);

        $this->configure($options);
    }

    /**
     * Removes subdomains and works out the root domain
     * 
     * @param string $domainb The $_SERVER['SERVER_NAME']
     * 
     * @return string The raw domain
     */
    private function generateDomain($domainb) {
        $bits = explode('/', $domainb);
        if ($bits[0] == 'http:' || $bits[0] == 'https:') {
            $domainb = $bits[2];
        } else {
            $domainb = $bits[0];
        }
        unset($bits);
        $bits = explode('.', $domainb);
        $idz = 0;

        while (isset($bits[$idz])) {
            $idz+=1;
        }

        $idz-=3;
        $idy = 0;
        while ($idy < $idz) {
            unset($bits[$idy]);
            $idy+=1;
        }
        $part = array();
        foreach ($bits AS $bit) {
            $part[] = $bit;
        }
        unset($bit);
        unset($bits);
        $domainb = '';
        if (isset($part[1]) && strlen($part[1]) > 3) {
            unset($part[0]);
        }
        foreach ($part AS $bit) {
            $domainb.=$bit . '.';
        }
        unset($bit);
        return preg_replace('/(.*)\./', '$1', $domainb);
    }

    /**
     *Takes the domain from $_SERVER and the calcuated raw domain and returns the current subdomain.
     * 
     * @param string $domainb $_SERVER['SERVER_NAME']
     * @param string $domain generateDomain or admin set domain
     * 
     * @return string the current subdomain
     */
    private function generateSubDomain($domainb, $domain) {
        $bits = explode('/', $domainb);
        if ($bits[0] == 'http:' || $bits[0] == 'https:') {
            $domainb = $bits[2];
        } else {
            $domainb = $bits[0];
        }
        if(empty($domainb)){
            return '';
        }
        $bits = explode($domain, $domainb);
        return trim($bits[0], '.');
    }

    /**
     * Checks the current subdomain to the requested one
     * 
     * @param string $subdomain The current subdomain
     * 
     * @return boolean
     */
    private  function isSameSubdomain($subdomain) {
        if ($subdomain == "DEFAULT") {
            $subdomain = dmConfig::get('site_subdomain_default');
        }
        return $subdomain == $this->subdomain;
    }
    
    /**
     * Adds a leading / from prefix and removes  trailing /
     * 
     * @param string $prefix
     * 
     * @return string
     */
    private function checkPrefix($prefix){
        if(substr($prefix,0,1) != '/'){
            $prefix = '/'.$prefix;
        }
        if(substr($prefix,strlen($prefix)-1,1) == '/'){
            $prefix = substr($prefix,0,-1);
        }
        return $prefix;
    }

    /**
     * Returns the current domain
     *
     * @return string
     */
   public function getDomain(){
        return $this->domain;
    }

    /**
     * Returns the current domain
     *
     * @return string
     */
    public function printDomain(){
        return $this->domain;
    }

    /**
     * Sets the subdomain for use of internal scripts (xmlSitemap) using 127.0.0.1
     *
     * @param string $domain
     * @return dmDomain
     */
    public function setDomain($domain){
        $this->domain = $this->generateDomain($domain);
        return $this;
    }

    /**
     * Returns the number of subdomains in the database for the given culture
     *
     * @param string $culture
     * @return integer
     */
    public function countSubdomains($culture = null){
        return dmDb::table('DmPage')->countSubdomains($culture);
    }

    /**
     * True if countSubdomains > 1
     *
     * @param string $culture
     * @return boolean
     */
    public function hasSubdomains($culture = null){
        return ($this->countSubdomains() > 1);
    }

    /**
     * Returns doctrineCollection of pages, one for each culture
     *
     * @param string $culture
     * @return doctrineCollection
     */
    public function getSubdomains($culture = null){
        return dmDb::table('DmPage')->getSubdomains($culture);
    }

    /**
     * Gets the current pages subdomain
     *
     * @return string - DEFAULT or current subdomain
     */
    public function getSubdomain(){
        if ($this->subdomain == dmConfig::get('site_subdomain_default')) {
            return "DEFAULT";
        }
        return $this->subdomain;
    }

    /**
     * Converts DEFAULT into printable subdomain and appends a '.' to the end for simplicity of printing
     *
     * @param string $subdomain
     * @return string
     */
    public function printSubdomain($subdomain){
        if ($subdomain == "DEFAULT") {
            $subdomain = dmConfig::get('site_subdomain_default');
        }

        return (!empty($subdomain)?$subdomain.".":"");
    }

    /**
     * Creates a full HTTP slug if the current subdomain is different from the given one, or if requested by  boolean. Else a half slug is returned.
     *
     * @param string $prefix - prefix (index.php)
     * @param string $slug - The page slug
     * @param string $subdomain - the subdomain of the page
     * @param boolean $full - default FALSE, set to TRUE to print full domain string instead of half.
     * @return string 
     */
    public function returnLink($prefix, $slug, $subdomain,$full = false){
        if(substr($slug,0,1) == '/'){
            $slug = substr($slug,1);
        }
        if($this->isSameSubdomain($subdomain) && !$full){
            $baseHref = $this->checkPrefix($prefix).'/'.$slug;
        }else{
            $baseHref = "http://" . $this->printSubdomain($subdomain) . $this->printDomain() . $this->checkPrefix($prefix).'/'.$slug;
        }
        
        if(substr($baseHref,strlen($baseHref)-1,1) == '/'){
            $baseHref = substr($baseHref,0,-1);
        }

        if(empty($baseHref))
        {
          $baseHref = '/';
        }

        return $baseHref;
    }
}