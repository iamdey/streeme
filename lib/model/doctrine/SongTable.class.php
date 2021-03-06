<?php

/**
 * SongTable
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class SongTable extends Doctrine_Table
{
  /**
   * Returns an instance of this class.
   *
   * @return object SongTable
   */
  public static function getInstance()
  {
    return Doctrine_Core::getTable('Song');
  }
  
  /**
   * Add a song to the library
   *
   * @param artist_id  int: the related artist primary key
   * @param album_id   int: the related album primary key
   * @param genre_id   int: the related genre primary key
   * @param song_array array: the array of song info
   * @return          int: the song insert id
   * @see apps/client/lib/MediaScan.class.php for information about the song_array
   */
  public function addSong( $artist_id, $album_id, $genre_id, $last_scan_id, $song_array )
  {
    if(
      isset( $song_array['filename'] )
      &&
      !empty( $song_array['filename'] )
      &&
      isset( $song_array['mtime'] )
      &&
      !empty( $song_array['mtime'] )
      &&
      $last_scan_id
    )
    {
      $song = new Song();
      $song->unique_id = sha1( uniqid( '', true ) . mt_rand( 1, 99999999 ) );
      $song->artist_id = (int) $artist_id;
      $song->album_id = (int) $album_id;
      $song->genre_id = (int) ( ( $genre_id ) ? $genre_id : $song_array[ 'id3_genre_id' ] );
      $song->last_scan_id = (int) $last_scan_id;
      $song->name = $song_array[ 'song_name' ];
      $song->length = $song_array[ 'song_length' ];
      $song->accurate_length = (int) $song_array[ 'accurate_length' ];
      $song->filesize = (int) $song_array[ 'filesize' ];
      $song->bitrate = (int) $song_array[ 'bitrate' ];
      $song->yearpublished = (int) $song_array[ 'yearpublished' ];
      $song->tracknumber = (int) $song_array[ 'tracknumber' ];
      $song->label = $song_array[ 'label' ];
      $song->mtime = (int) $song_array[ 'mtime' ];
      $song->atime = (int) $song_array[ 'atime' ];
      $song->filename = $song_array[ 'filename' ];
      $song->save();
      $id = $song->getId();
      $song->free();
      
      return $id;
    }
    return false;
  }
  
  /**
   * Find a song record by filename and mtime
   *
   * @param filename str: the itunes style filename of the file
   * @param mtime    int: the timestamp we're looking for
   * @return         object single DQL fetchone
   */
  public function findByFilenameAndMtime( $filename, $mtime )
  {
    $q = Doctrine_Query::create()
      ->from( 'Song s' )
      ->where( 's.mtime = ?', $mtime )
      ->andWhere( 's.filename = ?', $filename );
    return $q->fetchOne();
  }
  
  /**
   * Fetch a single song by its unique id
   *
   * @param unique_song_id str: unique_id field
   * @return               obj: single DQL fetchone with the song row
   */
  public function getSongByUniqueId( $unique_song_id )
  {
    //get the song from the database
    $q = Doctrine_Query::create()
          ->from( 'Song s' )
          ->where( 's.unique_id = ?', $unique_song_id );
    return $q->fetchOne();
  }
  
  /**
   * Try to mark a song as scanned by filename and mtime
   *
   * @param filename str: the itunes style filename of the file
   * @param mtime    int: the timestamp we're looking for
   * @param last_scan_id int: scan id value to update
   * @return         rows affected
   */
  public function updateScanId( $filename, $mtime, $last_scan_id )
  {
    $query  = 'UPDATE ';
    $query .= ' song ';
    $query .= 'SET ';
    $query .= ' last_scan_id = :last_scan_id ';
    $query .= 'WHERE ';
    $query .= ' mtime = :mtime ';
    $query .= ' AND filename = :filename ';
    
    $parameters = array();
    $parameters['last_scan_id'] = $last_scan_id;
    $parameters['mtime'] = $mtime;
    $parameters['filename'] = $filename;
  
    $dbh = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
    $stmt = $dbh->prepare( $query );
    $success = $stmt->execute( $parameters );
    if( $success )
    {
      return $stmt->rowCount();
    }
    else
    {
      return 0;
    }
  }
  
  /**
   * Fetch a list of albums that have not been scanned for art yet
   * @param source str: the artwork source: amazon|meta|folders|service etc.
   * @return       array: unscanned artwork list
   */
  public function getUnscannedArtList( $source )
  {
    $query  = 'SELECT DISTINCT ';
    $query .= ' album.id as album_id, album.name as album_name, artist.name as artist_name, song.filename as song_filename ';
    $query .= 'FROM ';
    $query .= ' song ';
    $query .= 'LEFT JOIN ';
    $query .= ' album ON song.album_id = album.id ';
    $query .= 'LEFT JOIN ';
    $query .= ' artist ON song.artist_id = artist.id ';
    $query .= 'WHERE ';
    $query .= ' album.id IS NOT NULL ';
    switch ( $source )
    {
      case 'amazon':
        $query .= ' AND album.amazon_flagged != 1 ';
        break;
      
      case 'meta':
        $query .= ' AND album.meta_flagged != 1 ';
        break;
        
      case 'folders':
        $query .= ' AND album.folders_flagged != 1 ';
        break;
        
      case 'service':
        $query .= ' AND album.service_flagged != 1 ';
        break;
    }
    $query .= ' AND album.has_art != 1 ';
    $query .= ' ORDER BY album.id ASC ';
    
    $dbh = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
    return $dbh->query( $query )->fetchAll();
  }
  
  /**
   * Get file list
   * @param id   str: song unique id | album and artist id
   * @param type str: song | album | artists
   * @return     array: list of filenames for each song
   */
  public function getFileList( $id, $type )
  {
    $q = Doctrine_Query::create()
      ->select( 's.filename' )
      ->from( 'Song s' )
      ->where( '( 1 = 1 )' );
    switch( $type )
    {
      case 'artist':
        $q->andWhere( 's.artist_id = ?', $id );
        break;
      case 'album':
        $q->andWhere( 's.album_id = ?', $id );
        break;
      case 'song':
        $q->andWhere( 'unique_id = ?', $id );
        break;
    }
    return $q->fetchArray();
  }
  
  /**
   * Get total song count
   * @return       int: total album count in database
   */
  public function getTotalSongCount()
  {
    $q = Doctrine_Query::create()
      ->select( 's.id' )
      ->from( 'Song s' );
    return $q->count();
  }
  
  /**
   * Get a list of songs
   * @param parameters    array: search and pagination options
   * @param result_count  OUT int: the resulting number of records in search before pagination
   * @param result_list   OUT array: the resulting data set
   * @return              bool: true if results exist, otherwise false
   * @see paramters list below in 'list defaults'
   */
  public function getList( $parameters=array(), &$result_count, &$result_list )
  {
    //list defaults
    $settings = array(
                         'limit'          => 50,     //int
                         'offset'         => '0',    //int
                         'order'          => 'desc', //str: asc|desc
                         'search'         => null,   //str
                         'artist_id'      => null,   //int
                         'album_id'       => null,   //int
                         'song_id'        => null,   //int
                         'genre_id'       => null,   //int
                         'playlist_id'    => null,   //int
                         'sortcolumn'     => 0,      //int
                         'sortdirection'  => 'desc', //str: asc|desc
                         'random'         => false,  //bool
                         'by_alpha'       => null,   //str: A-Z
                         'by_number'      => null,   //
                      );
    $result_count = 0;
    $result_list = array();
                      
    //import user paramters
    foreach ( $parameters as $name => $value )
    {
       $settings[ $name ] = $value;
    }
    
    //check for special space-separated search syntax eg( shuffle:true artistid:1 )
    $components = explode ( ' ', $settings[ 'search' ] );
    foreach( $components as $k=>$v )
    {
       //if playlistid: is set, change to a playlist songlist
       if ( stristr( $v, 'playlistid:' ) )
       {
         $match = explode( ':', $v );
         if ( is_array( $match ) )
         {
            $settings[ 'playlist_id' ] = $match[1];
            unset( $components[ $k ] );
         }
       }
    
       //if artistid: is set, add artistid to the where clause
       if ( stristr( $v, 'artistid:' ) )
       {
         $match = explode( ':', $v );
         if ( is_array( $match ) )
         {
            $settings[ 'artist_id' ] = $match[1];
            unset( $components[ $k ] );
         }
       }
    
       //if albumid: is set, add albumid to the where clause
       if ( stristr( $v, 'albumid:' ) )
       {
         $match = explode( ':', $v );
         if ( is_array( $match ) )
         {
            $settings[ 'album_id' ] = $match[1];
            unset( $components[ $k ] );
         }
       }
    
       //if genreid: is set, add genreid to the where clause
       if ( stristr( $v, 'genreid:' ) )
       {
         $match = explode( ':', $v );
         if ( is_array( $match ) )
         {
            $settings[ 'genre_id' ] = $match[1];
            unset( $components[ $k ] );
         }
       }
        
       //if by_alpha: is set, add an alpha LIKE to the where clause
       if ( stristr( $v, 'by_alpha:' ) )
       {
         $match = explode( ':', $v );
         if ( is_array( $match ) )
         {
            if ( $match[1] != "#" )
            {
               $settings[ 'by_alpha' ] = $match[1];
               unset( $components[ $k ] );
            }
            else
            {
               $settings[ 'by_number' ] = $match[1];
               unset( $components[ $k ] );
            }
         }
       }
  
       //if shuffle: is set, add genreid to the where clause
       if ( stristr( $v, 'shuffle:' ) )
       {
         $match = explode( ':', $v );
         if ( is_array( $match ) )
         {
            $settings[ 'random' ] = true;
            $settings[ 'sortcolumn' ] = 8;
            unset( $components[ $k ] );
         }
       }
    }
    
    //search should now be valid keywords, join them with spaces
    $settings[ 'search' ] = join( ' ', $components );
  
      //this array contains the decoded sort information
    $order_by = ( $settings['sortdirection'] == 'asc') ? ' ASC ' : ' DESC ';
    $column_sql = array(
                        0 => ' song.id ' . $order_by,
                        1 => ' song.name ' . $order_by,
                        2 => ' album.name ' . $order_by . ', song.tracknumber ASC ',
                        3 => ' artist.name ' . $order_by . ', album.name DESC, song.tracknumber ASC ',
                        4 => ' album_mtime ' . $order_by .  ', album.id, song.tracknumber ASC ',
                        5 => ' song.yearpublished ' . $order_by . ', album.name DESC, song.tracknumber ASC ',
                        6 => ' song.length ' . $order_by,
                        7 => ' song.tracknumber ' . $order_by,
                        8 => ' RAND() '
                     );
    $order_by_string = $column_sql[ (int) $settings[ 'sortcolumn' ] ];
    
    $parameters = array();
    
    $query  = 'SELECT ';
    $query .= ' song.unique_id, song.name, album.name as album_name, artist.name as artist_name, song.mtime as date_modified, song.yearpublished, song.length, song.tracknumber, song.filename, ROUND( song.mtime / 20000 ) as album_mtime ';
    $query .= 'FROM ';
    if( !is_null( $settings['playlist_id'] ) )
    {
      $query .= ' playlist_files, ';
    }
    $query .= ' song ';
    $query .= 'LEFT JOIN ';
    $query .= ' artist ';
    $query .= 'ON song.artist_id = artist.id ';
    $query .= 'LEFT JOIN ';
    $query .= ' album ';
    $query .= 'ON song.album_id = album.id ';
    $query .= 'LEFT JOIN ';
    $query .= ' genre ';
    $query .= 'ON song.genre_id = genre.id ';
    $query .= 'WHERE ( 1 = 1 ) ';
    if( !is_null( $settings['playlist_id'] ) )
    {
      $query .= ' AND playlist_files.playlist_id = :playlist_id ';
      $query .= ' AND playlist_files.filename = song.filename ';
      $parameters[ 'playlist_id' ] = $settings[ 'playlist_id' ];
    }
    if ( !is_null(  $settings[ 'song_id' ] ) )
    {
      $query .= ' AND song.id = :song_id ';
      $parameters[ 'song_id' ] = $settings[ 'song_id' ];
    }
    if ( !is_null(  $settings[ 'album_id' ] ) )
    {
      $query .= ' AND song.album_id = :album_id ';
      $parameters[ 'album_id' ] = $settings[ 'album_id' ];
    }
    if ( !is_null(  $settings[ 'artist_id' ] ) )
    {
      $query .= ' AND song.artist_id = :artist_id ';
      $parameters[ 'artist_id' ] = $settings[ 'artist_id' ];
    }
    if ( !is_null(  $settings[ 'genre_id' ] ) )
    {
      $query .= ' AND song.genre_id = :genre_id ';
      $parameters[ 'genre_id' ] = $settings[ 'genre_id' ];
    }
    if ( !is_null(  $settings[ 'by_alpha' ] ) )
    {
      $query .= ' AND song.name LIKE :by_alpha ';
      $parameters[ 'by_alpha' ] = $settings[ 'by_alpha' ] . '%';
    }
    if ( !is_null(  $settings[ 'by_number' ] ) )
    {
      $query .= ' AND ( song.name LIKE "0%" ';
      $query .= ' OR song.name LIKE "1%" ';
      $query .= ' OR song.name LIKE "2%" ';
      $query .= ' OR song.name LIKE "3%" ';
      $query .= ' OR song.name LIKE "4%" ';
      $query .= ' OR song.name LIKE "5%" ';
      $query .= ' OR song.name LIKE "6%" ';
      $query .= ' OR song.name LIKE "7%" ';
      $query .= ' OR song.name LIKE "8%" ';
      $query .= ' OR song.name LIKE "9%" ) ';
    }
    if ( !is_null(  $settings[ 'search' ] ) && ( !empty( $settings[ 'search' ] ) || $settings[ 'search' ] === '0'  ) )
    {
      $query .= ' AND ( song.name LIKE :search OR album.name LIKE :search OR artist.name LIKE :search ) ';
      $parameters[ 'search' ] = '%' . join('%', explode(' ', $settings[ 'search' ] ) ) . '%';
    }

    //get a count of rows returned by this query before applying pagination
    $dbh = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
    $stmt = $dbh->prepare( $query );
    $success = $stmt->execute( $parameters );
    if( $success )
    {
      $row_count = $stmt->rowCount();
      
      if( $row_count > 1 )
      {
        //most databases have an optimized rowCount API
        $result_count = $row_count;
      }
      else
      {
        //sqlite compatibility: rowCount will only return 0 or 1
        while( $row = $stmt->fetch() ) $result_count++;
      }
    }
    else
    {
      return false;
    }
    
    //get the data set with pagination and ordering
    $query .= 'ORDER BY ';
    $query .= $order_by_string . ' ';
    $query .= ' LIMIT ';
    $query .= (int) $settings[ 'limit' ];
    $query .= ' OFFSET ';
    $query .= (int) $settings[ 'offset' ];
    $dbh = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
    $stmt = $dbh->prepare( $query );
    $success = $stmt->execute( $parameters );
    if( $success )
    {
      $result_list = $stmt->fetchAll(Doctrine::FETCH_ASSOC);
      return true;
    }
    else
    {
      return false;
    }
  }
  
  /**
   * Remove song records not found in the specified scan
   *
   * @param last_scan_id int: this should be the id of the latest library scan
   * @return             array: number of records removed
   */
  public function finalizeScan( $last_scan_id )
  {
    $q = Doctrine_Query::create()
      ->delete('Song s')
      ->where('s.last_scan_id != ?', $last_scan_id )
      ->execute();
    return $q;
  }
}