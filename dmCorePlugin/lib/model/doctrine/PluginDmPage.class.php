<?php

/**
 * PluginDmPage
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5845 2009-06-09 07:36:57Z jwage $
 */
abstract class PluginDmPage extends BaseDmPage
{
  protected
  $nameBackup;

  /**
   * Is this page source referring to me ?
   */
  public function isSource($source)
  {
    return ($page = $this->getTable()->findOneBySource($source)) && $page->get('id') === $this->get('id');
  }

  /**
   * Is this this page source referring to a parent of mine ?
   */
  public function isDescendantOfSource($source)
  {
    return ($page = $this->getTable()->findOneBySource($source)) && $this->getNode()->isDescendantOf($page);
  }

  /**
   * An automatic page represents an myDoctrineRecord object ( article, product... )
   * It will be created, updated and deleted according to its object
   * Automatic pages with the same module will share the same DmPageView & DmAutoSeo
   */
  public function getIsAutomatic()
  {
    if ($this->hasCache('is_automatic'))
    {
      return $this->getCache('is_automatic');
    }

    return $this->setCache('is_automatic', $this->get('action') === 'show');
  }

  public function hasRecords()
  {
    return $this->get('module') === 'list';
  }

  public function hasRecord()
  {
    return 0 != $this->get('record_id');
  }

  /**
   * Get the record related to this page, if any
   * 
   * @return dmDoctrineRecord the related record
   */
  public function getRecord()
  {
    if ($this->hasCache('record'))
    {
      return $this->getCache('record');
    }
    
    if (($module = $this->getDmModule()) && ($table = $module->getTable()))
    {
      $record = $table->createQuery('r')
      ->where('r.id = ?', $this->get('record_id'))
      ->withI18n(null, $table->getComponentName(), 'r')
      ->fetchOne();
    }
    else
    {
      $record = false;
    }
    
    return $this->setCache('record', $record);
  }

  public function setRecord(dmDoctrineRecord $record)
  {
    if ($record->getDmModule()->getKey() != $this->get('module'))
    {
      throw new dmException('Assigning record with wrong module');
    }

    return $this->setCache('record', $record);
  }

  /*
   * When the is_active value of the page is set manually,
   * if the page has an activable record,
   * the record is_active field is synced
   */
  public function setIsActiveManually($value = null)
  {
    $value = null === $value ? $this->get('is_active') : (bool)$value;
    
    $this->set('is_active', $value);

    if($record = $this->getRecord())
    {
      if($record->getTable()->hasField('is_active'))
      {
        $record->set('is_active', $value);
      }
    }

    return $this;
  }

  public function getDmModule()
  {
    if($this->hasCache('dm_module'))
    {
      return $this->getCache('dm_module');
    }

    if(!$moduleManager = $this->getModuleManager())
    {
      return null;
    }

    return $this->setCache('dm_module', $moduleManager->getModuleOrNull($this->get('module')));
  }

  public function getPageView()
  {
    if($this->hasCache('page_view'))
    {
      return $this->getCache('page_view');
    }

    $pageView = dmDb::query('DmPageView p, p.Layout l')
    ->where('p.module = ? AND p.action = ?', array($this->get('module'), $this->get('action')))
    ->fetchOne();
    
    if(!$pageView)
    {
      $pageView = dmDb::table('DmPageView')->createFromModuleAndAction($this->get('module'), $this->get('action'));
    }

    return $this->setCache('page_view', $pageView);
  }

  public function setPageView(DmPageView $pageView, $check = true)
  {
    if ($check)
    {
      if ($pageView->get('module') != $this->get('module'))
      {
        throw new dmException('Assigning page view with wrong module');
      }
      if ($pageView->get('action') != $this->get('action'))
      {
        throw new dmException('Assigning page view with wrong action');
      }
    }

    return $this->setCache('page_view', $pageView);
  }

  public function getModuleAction()
  {
    return $this->get('module').'/'.$this->get('action');
  }

  public function isModuleAction($module, $action)
  {
    return $this->get('module') == $module && $this->get('action') == $action;
  }

  /**
   * Same as getNode()->getParent()->id
   * but will not hydrate full parent
   */
  public function getNodeParentId()
  {
    if (!$this->get('lft'))
    {
      return null;
    }

    $stmt = Doctrine_Manager::connection()->prepare('SELECT p.id
FROM '.$this->getTable()->getTableName().' p
WHERE p.lft < ? AND p.rgt > ?
ORDER BY p.rgt ASC
LIMIT 1')->getStatement();

    $stmt->execute(array($this->get('lft'), $this->get('rgt')));
    
    return $stmt->fetchColumn();
  }

  public function save(Doctrine_Connection $conn = null)
  {
    $record = $this->getRecord();

    if($record && $record->isModified())
    {
      $record->save();
    }

    if ($this->isModified())
    {
      if (!$this->isNew() && ($this->isFieldModified('module') || $this->isFieldModified('action')))
      {
        if ($pageView = dmDb::table('DmPageView')->findOneByModuleAndAction($this->get('module'), $this->get('action')))
        {
          $this->setPageView($pageView, false);
        }
        else
        {
          $this->getPageView()->fromArray(array(
            'module' => $this->get('module'),
            'action' => $this->get('action')
          ));
        }
      }

      $this->getPageView();

      if ($this->getDmModule() && $this->getIsAutomatic() && !$record instanceof dmDoctrineRecord)
      {
        throw new dmException(sprintf(
          '%s automatic page can not be saved because it has no object for record_id = %s',
          $this, $this->record_id
        ));
      }
    }

    $translationModifiedFields = $this->hasCurrentTranslation() ? $this->getCurrentTranslation()->getModified() : array();

    parent::save($conn);

    if(array_key_exists('slug', $translationModifiedFields))
    {
      if(!$this->getTable()->isSlugUnique($this->get('slug'), $this->get('id'),$this->get('subdomain')))
      {
        $this->set('slug', $this->getTable()->createUniqueSlug($this->get('slug'), $this->get('id'), null, $this->get('subdomain')));
        return $this->save();
      }
    }

    if ($dispatcher = $this->getEventDispatcher())
    {
      $dispatcher->notify(new sfEvent($this, 'dm.page.post_save'));
    }
  }
  
  public function preDelete($event)
  {
    parent::preDelete($event);
    
    $this->nameBackup = $this->get('name');
  }
  
  public function getNameBackup()
  {
    return $this->nameBackup;
  }

  public function __toString()
  {
    return $this->nameBackup ? $this->nameBackup : sprintf('#%d %s.%s',
      $this->get('id'),
      $this->get('module'),
      $this->get('action')
    );
  }

  /**
   * SEO methods
   */

  public function getMyAutoSeoFields()
  {
    $fields = array();
    
    foreach($this->getAutoSeoFields() as $field)
    {
      if ($this->isSeoAuto($field))
      {
        $fields[] = $field;
      }
    }

    return $fields;
  }

  public function getAutoSeoFields()
  {
    return $this->getRecord()->getTable()->getAutoSeoFields();
  }

  /**
   * @return boolean true if the field must be setted automatically
   */
  public function isSeoAuto($seoField)
  {
    return strpos($this->get('auto_mod'), $seoField{0}) !== false;
  }
  
  /**
   * Update auto_mod field according to modified fields
   * when fieds are updated manualy
   * if description has been changed,
   * the letter 'd' will be removed from auto_mod
   * but if new description is empty,
   * the letter 'd' will be added to auto_mod
   *
   * @return DmPage $this
   */
  public function updateAutoModFromModified()
  {
    if (!$this->getIsAutomatic())
    {
      return $this;
    }
    
    $modifiedFields = $this->get('Translation')->get(self::getDefaultCulture())->getModified();
    
    foreach($this->getAutoSeoFields() as $seoField)
    {
      if(isset($modifiedFields[$seoField]))
      {
        if (empty($modifiedFields[$seoField]) && !$this->isSeoAuto($seoField))
        {
          $this->set('auto_mod', $this->get('auto_mod').$seoField{0});
        }
        if (!empty($modifiedFields[$seoField]) && $this->isSeoAuto($seoField))
        {
          $this->set('auto_mod', str_replace($seoField{0}, '', $this->get('auto_mod')));
        }
      }
    }
    
    return $this;
  }
  
  /**
   * Get html produced by widgets in this page
   * usefull for search engine indexation
   */
  public function getIndexableContent()
  {
    $command = sprintf('dmFront:page-indexable-content %d %s', $this->get('id'), self::getDefaultCulture());
    
    $filesystem = $this->getServiceContainer()->getService('filesystem');
    
    $filesystem->sf($command);
    
    return $filesystem->getLastExec('output');
  }
}
