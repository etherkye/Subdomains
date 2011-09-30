<?php

/*
 * This file is part of the dmCorePlugin package.
 * (c) 2011 Diem project
 *
 *  http://www.diem-project.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @see sfSessionStorage
 *
 */
class dmSessionStorage extends sfSessionStorage {

  protected
  $serviceContainer;

  public function initialize($options = null) {
    if (!isset($options['session_cookie_domain']) || $options['session_cookie_domain'] == '') {
      $options['session_cookie_domain'] = ((isset($_SERVER['SERVER_NAME'])) ? '.' . $this->generateDomain($_SERVER['SERVER_NAME']) : '');
    }
    parent::initialize($options);
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
    if (isset($part[1]) && strlen($part[1]) > 3) {
      unset($part[0]);
    }
    foreach ($part AS $bit) {
      $domainb.=$bit . '.';
    }
    unset($bit);
    return preg_replace('/(.*)\./', '$1', $domainb);
  }

}