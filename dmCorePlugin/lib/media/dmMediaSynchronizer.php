<?php

class dmMediaSynchronizer
{
  protected
  $filesystem,
  $folderTable,
  $mediaTable,
  $cdnSettings,
  $cdnIncrements,
  $ignore = array('.', '..', '.thumbs', '.svn', '_svn', 'CVS', '_darcs', '.arch-params', '.monotone', '.bzr', '.git', '.hg');

  public function __construct(dmFilesystem $filesystem)
  {
    $this->filesystem = $filesystem;

    $this->folderTable  = dmDb::table('DmMediaFolder');
    $this->mediaTable   = dmDb::table('DmMedia');

    $this->cdnSettings = array("default" => array_merge(array('number' => 2), sfConfig::get('dm_cdn_default',array())));
    if(isset($this->cdnSettings['default']['list'])){
      $this->cdnSettings['default']['number'] = count($settings['default']['list']);
    }
    $this->cdnIncrements = array();
  }

  public function execute(DmMediaFolder $folder, $depth = 99, $cdn = false)
  {
    if($depth < 1)
    {
      return;
    }
    
    /*
     * Clear php filesystem cache
     * This will avoid some problems
     */
    clearstatcache();

    //$folder->refresh(true);

    list($dirs, $files) = $this->getDirContents($folder->getFullPath());

    $medias   = $folder->getDmMediasByFileName();
    $children = $folder->getSubfoldersByName();

    $dirty = false;
    
    /*
     * 1. Add new files to the medias
     * Also now redoes the CDN network.
     */
    foreach($files as $file)
    {
      $fileBasename = basename($this->sanitizeFile($file));

      if (!array_key_exists($fileBasename, $medias))
      {
        // File exists, media does not exist: create media
        $media = $this->mediaTable->create(array(
          'dm_media_folder_id' => $folder->get('id'),
          'file' => $fileBasename
        ))->save();

        $dirty = true;
      }
      else
      {
        $media = $medias[$fileBasename];
        // File exists, media exists: do nothing
        unset($medias[$fileBasename]);
      }

      if($cdn){
        /*
         * Loads CDN data for mime type if it's not already set
         */
        if(!isset($this->cdnIncrements[$media->get('mime')])){
          $this->cdnIncrements[$media->get('mime')] = -1;
          $this->cdnSettings[$media->get('mime')] = array_merge($this->cdnSettings['default'],sfConfig::get('dm_cdn_'.$media->get('mime'),array()));
          if(isset($this->cdnSettings[$media->get('mime')]['list'])){
            $this->cdnSettings[$media->get('mime')]['number'] = count($this->cdnSettings[$media->get('mime')]['list']);
          }
        }
        /*
         * Sets the CDN data for that mime type.
         */
        $this->cdnIncrements[$media->get('mime')] = ($this->cdnIncrements[$media->get('mime')]+1)%$this->cdnSettings[$media->get('mime')]['number'];
        $media->setCdn($this->cdnIncrements[$media->get('mime')]);
        $media->save();
        $media->free();
      }
      unset($media); 
    }

    /*
     * 2. Remove medias which have no file
     */
    foreach ($medias as $name => $media)
    {
      // File does not exist, media exists: delete media
      try
      {
        $media->delete();
      }
      catch(Doctrine_Connection_Exception $e)
      {
        //A record needs this media, but the file has been removed :-/
      }
      
      $dirty = true;
    }

    foreach($dirs as $dir)
    {
      $dirName = basename($this->sanitizeDir($dir));

      /*
       * Exists in fs, not in db
       */
      if (!array_key_exists($dirName, $children))
      {
        $subfolderRelPath = trim(dmOs::join($folder->get('rel_path'), $dirName), '/');

        if ($child = $this->folderTable->findOneByRelPath($subfolderRelPath))
        {
          // folder exists in db but is not linked to its parent
          $child->getNode()->moveAsLastChildOf($folder);

          $child->refresh();

          $dirty = true;
        }
        else
        {
          // dir exists in filesystem, not in database: create folder in database
          $child = $this->folderTable->create(array(
            'rel_path' => $subfolderRelPath
          ));

          $child->getNode()->insertAsLastChildOf($folder);

          $child->refresh();

          $dirty = true;
        }
      }
      else
      {
        // dir exists in filesystem and database: do nothing
        $child = $children[$dirName];
        unset($children[$dirName]);
      }

      $this->execute($child, $depth - 1,$cdn);
    }

    /*
     * Not unsetted folders
     * don't exist in fs
     */
    foreach ($children as $child)
    {
      try
      {
        $child->getNode()->delete();
      }
      catch(Doctrine_Connection_Exception $e)
      {
        //A record needs a media in this folder, but the folder has been removed :-/
        $this->filesystem->mkdir($child->getFullPath());
      }

      $dirty = true;
    }

    if($dirty)
    {
      $folder->clearCache()->refresh()->refreshRelated('Medias');
    }
  }

  /**
   * Sanitize files name (moves file if non regular name)
   */
  protected function sanitizeFile($file)
  {
    if (basename($file) != dmOs::sanitizeFileName(basename($file)))
    {
      $renamedFile = dmOs::join(dirname($file), dmOs::sanitizeFileName(basename($file)));

      while(file_exists($renamedFile))
      {
        $renamedFile = dmOs::randomizeFileName($renamedFile);
      }
      
      rename($file, $renamedFile);
      $file = $renamedFile;
    }

    return $file;
  }

  /**
   * Sanitize dirs name (move dirs)
   */
  protected function sanitizeDir($dir)
  {
    if (basename($dir) != dmOs::sanitizeDirName(basename($dir)))
    {
      $renamedDir = dmOs::join(dirname($dir), dmOs::sanitizeDirName(basename($dir)));
      
      while(is_dir($renamedDir))
      {
        $renamedDir = dmOs::randomizeDirName($renamedDir);
      }
      
      rename($dir, $renamedDir);
      $dir = $renamedDir;
    }

    return $dir;
  }

  protected function getDirContents($dir)
  {
    $files = $dirs = array();

    if(!is_dir($dir))
    {
      return array($dirs, $files);
    }

    if($resource = opendir($dir))
    {
      while (false !== $entryName = readdir($resource))
      {
        if (in_array($entryName, $this->ignore))
        {
          continue;
        }

        $currentEntry = $dir.'/'.$entryName;

        if (is_file($currentEntry))
        {
          $files[] = $currentEntry;
        }
        elseif (is_dir($currentEntry))
        {
          $dirs[] = $currentEntry;
        }
      }
      closedir($resource);
    }

    return array($dirs, $files);
  }
}
