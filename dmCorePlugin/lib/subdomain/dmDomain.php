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
        if (strlen($part[1]) > 3) {
            unset($part[0]);
        }
        foreach ($part AS $bit) {
            $domainb.=$bit . '.';
        }
        unset($bit);
        return preg_replace('/(.*)\./', '$1', $domainb);
    }

    private function generateSubDomain($domainb, $domain) {
        $bits = explode('/', $domainb);
        if ($bits[0] == 'http:' || $bits[0] == 'https:') {
            $domainb = $bits[2];
        } else {
            $domainb = $bits[0];
        }
        $bits = explode($domain, $domainb);
        return trim($bits[0], '.');
    }

    private  function isSameSubdomain($subdomain) {
        if ($subdomain == "DEFAULT") {
            $subdomain = dmConfig::get('site_subdomain_default');
        }
        return $subdomain == $this->subdomain;
    }
    private function checkPrefix($prefix){
        if(substr($prefix,0,1) != '/'){
            $prefix = '/'.$prefix;
        }
        if(substr($prefix,strlen($prefix)-1,1) != '/'){
            $prefix = $prefix.'/';
        }
        return $prefix;
    }

    public function getDomain(){
        return $this->domain;
    }
    public function printDomain(){
        return $this->domain;
    }

    public function countSubdomains($culture = null){
        return dmDb::table('DmPage')->countSubdomains($culture);
    }
    public function hasSubdomains($culture = null){
        return ($this->countSubdomains() > 1);
    }
    public function getSubdomains($culture = null){
        return dmDb::table('DmPage')->getSubdomains($culture);
    }

    public function getSubdomain(){
        if ($this->subdomain == dmConfig::get('site_subdomain_default')) {
            return "DEFAULT";
        }
        return $this->subdomain;
    }

    public function printSubdomain($subdomain){
        if ($subdomain == "DEFAULT") {
            $subdomain = dmConfig::get('site_subdomain_default');
        }

        return (!empty($subdomain)?$subdomain.".":"");
    }

    public function returnLink($prefix, $slug, $subdomain,$full = false){
        if($this->isSameSubdomain($subdomain) && !$full){
            $baseHref = $this->checkPrefix($prefix).$slug;
        }else{
            $baseHref = "http://" . $this->printSubdomain($subdomain) . $this->printDomain() . $this->checkPrefix($prefix).$slug;
        }

        if(empty($baseHref))
        {
          $baseHref = '/';
        }

        return $baseHref;
    }
}