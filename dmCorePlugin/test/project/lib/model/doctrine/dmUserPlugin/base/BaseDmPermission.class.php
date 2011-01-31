<?php

/**
 * BaseDmPermission
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property string $name
 * @property string $description
 * @property Doctrine_Collection $Users
 * @property Doctrine_Collection $Groups
 * 
 * @method string              getName()        Returns the current record's "name" value
 * @method string              getDescription() Returns the current record's "description" value
 * @method Doctrine_Collection getUsers()       Returns the current record's "Users" collection
 * @method Doctrine_Collection getGroups()      Returns the current record's "Groups" collection
 * @method DmPermission        setName()        Sets the current record's "name" value
 * @method DmPermission        setDescription() Sets the current record's "description" value
 * @method DmPermission        setUsers()       Sets the current record's "Users" collection
 * @method DmPermission        setGroups()      Sets the current record's "Groups" collection
 * 
 * @package    retest
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class BaseDmPermission extends myDoctrineRecord
{
    public function setTableDefinition()
    {
        $this->setTableName('dm_permission');
        $this->hasColumn('name', 'string', 255, array(
             'type' => 'string',
             'unique' => true,
             'length' => 255,
             ));
        $this->hasColumn('description', 'string', 5000, array(
             'type' => 'string',
             'length' => 5000,
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasMany('DmUser as Users', array(
             'refClass' => 'DmUserPermission',
             'local' => 'dm_permission_id',
             'foreign' => 'dm_user_id'));

        $this->hasMany('DmGroup as Groups', array(
             'refClass' => 'DmGroupPermission',
             'local' => 'dm_permission_id',
             'foreign' => 'dm_group_id'));

        $timestampable0 = new Doctrine_Template_Timestampable(array(
             ));
        $this->actAs($timestampable0);
    }
}