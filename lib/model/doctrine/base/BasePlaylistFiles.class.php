<?php
// Connection Component Binding
Doctrine_Manager::getInstance()->bindComponent('PlaylistFiles', 'doctrine');

/**
 * BasePlaylistFiles
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property integer $playlist_id
 * @property text $filename
 * 
 * @method integer       getId()          Returns the current record's "id" value
 * @method integer       getPlaylistId()  Returns the current record's "playlist_id" value
 * @method text          getFilename()    Returns the current record's "filename" value
 * @method PlaylistFiles setId()          Sets the current record's "id" value
 * @method PlaylistFiles setPlaylistId()  Sets the current record's "playlist_id" value
 * @method PlaylistFiles setFilename()    Sets the current record's "filename" value
 * 
 * @package    streeme
 * @subpackage model
 * @author     Richard Hoar
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class BasePlaylistFiles extends sfDoctrineRecord
{
    public function setTableDefinition()
    {
        $this->setTableName('playlist_files');
        $this->hasColumn('id', 'integer', null, array(
             'type' => 'integer',
             'primary' => true,
             'autoincrement' => true,
             ));
        $this->hasColumn('playlist_id', 'integer', null, array(
             'type' => 'integer',
             'default' => 0,
             ));
        $this->hasColumn('filename', 'text', null, array(
             'type' => 'text',
             ));

        $this->option('collate', 'utf8_unicode_ci');
        $this->option('charset', 'utf8');
    }

    public function setUp()
    {
        parent::setUp();
        
    }
}