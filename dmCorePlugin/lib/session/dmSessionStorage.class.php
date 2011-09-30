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
class dmSessionStorage extends sfSessionStorage
{

  protected
  $serviceContainer;

  public function initialize($options = null)
  {
    if(!isset($options['session_cookie_domain']) || $options['session_cookie_domain'] == ''){
      $options['session_cookie_domain']  = ((isset($_SERVER['SERVER_NAME']))?'.'.$_SERVER['SERVER_NAME']:'');
    }
    parent::initialize($options);
  }

}