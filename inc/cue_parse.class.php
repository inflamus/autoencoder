<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

/*
 * CueSheet parser by Nigel Barnes.
 *
 * This is a PHP conversion of CueSharp 0.5 by Wyatt O'Day (wyday.com/cuesharp)
 */

/**
 * A CueSheet class used to open and parse cuesheets.
 *
 */
class CueSheet
{
  var $cuesheet = array();

  /**
   * Parses a cue sheet file.
   *
   * @param string $filename - The filename for the cue sheet to open.
   */
  function readCueSheet($filename)
  {
    $cue_lines = array();
    foreach (explode("\n",str_replace("\r",null,file_get_contents($filename))) as $line)
    {
      if ( strlen($line) > 0 && $line[0] != '#')
        $cue_lines[] = trim($line);
    }
    $this->parseCueSheet($cue_lines);

    return $this->cuesheet;
  }

  /**
   * Parses the cue sheet array.
   *
   * @param array $file - The cuesheet as an array of each line.
   */
  function parseCueSheet($file)
  {
    //-1 means still global, all others are track specific
    $track_on = -1;

    for ($i=0; $i < count($file); $i++)
    {
      switch (strtoupper(array_shift(explode(' ', $file[$i]))))
      {
        case "CATALOG":
        case "CDTEXTFILE":
        case "ISRC":
        case "PERFORMER":
        case "SONGWRITER":
        case "TITLE":
          $this->parseString($file[$i], $track_on);
          break;
        case "FILE":
          $currentFile = $this->parseFile($file[$i]);
          break;
        case "FLAGS":
          $this->parseFlags($file[$i], $track_on);
          break;
        case "INDEX":
        case "POSTGAP":
        case "PREGAP":
          $this->parseIndex($file[$i], $track_on);
          break;
        case "REM":
          $this->parseComment($file[$i], $track_on);
          break;
        case "TRACK":
          $track_on++;
          $this->parseTrack($file[$i], $track_on);
          if (isset($currentFile)) //if there's a file
          {
            $this->cuesheet["TRACKS"][$track_on]["DATAFILE"] = $currentFile;
          }
          break;
        default:
          $this->parseGarbage($file[$i], $track_on);
          //save discarded junk and place string[] with track it was found in
          break;
      }
    }
  }

  /**
   * Parses the REM command.
   *
   * @param string $line - The line in the cue file that contains the TRACK command.
   * @param integer $track_on - The track currently processing.
   */
  function parseComment($line, $track_on)
  {
    //remove "REM"
    $line = substr($line, strpos($line,' ') + 1);

    if ( strlen($line) > 0 )
    {
      if ($track_on == -1)
      {
        $this->cuesheet["COMMENTS"][] = $line;
      }
      else
      {
        $this->cuesheet["TRACKS"][$track_on]["COMMENTS"][] = $line;
      }
    }
  }

  /**
   * Parses the FILE command.
   *
   * @param string $line - The line in the cue file that contains the FILE command.
   * @return array - Array of FILENAME and TYPE of file..
   */
  function parseFile($line)
  {
    $line = substr($line, strpos($line,' ') + 1);
    $type = substr($line, strrpos($line, ' '));

    //remove type
    $line = substr($line, 0, strrpos($line, ' ') - 1);

    //if quotes around it, remove them.
    $line = trim($line, '"');

    return array('FILENAME'=>$line, 'TYPE'=>$type);
  }

  /**
   * Parses the FLAG command.
   *
   * @param string $line - The line in the cue file that contains the TRACK command.
   * @param integer $track_on - The track currently processing.
   */
  function parseFlags($line, $track_on)
  {
    if ($track_on != -1)
    {
      foreach (explode(' ',strtoupper($line)) as $type)
      {
        switch ($type)
        {
          case "FLAGS":
          case "DATA":
          case "DCP":
          case "4CH":
          case "PRE":
          case "SCMS":
            $this->cuesheet["TRACKS"][$track_on]["FLAG"][] = $type;
            break;
          default:
            break;
        }
      }
    }
  }

  /**
   * Collect any unidentified data.
   *
   * @param string $line - The line in the cue file that contains the TRACK command.
   * @param integer $track_on - The track currently processing.
   */
  function parseGarbage($line, $track_on)
  {
    if ( strlen($line) > 0 )
    {
      if ($track_on == -1)
      {
        $this->cuesheet["GARBAGE"][] = $line;
      }
      else
      {
        $this->cuesheet["TRACKS"][$track_on]["GARBAGE"][] = $line;
      }
    }
  }

  /**
   * Parses the INDEX command of a TRACK.
   *
   * @param string $line - The line in the cue file that contains the TRACK command.
   * @param integer $track_on - The track currently processing.
   */
  function parseIndex($line, $track_on)
  {
    $type = substr($line, 0, strpos($line, ' '));
    $line = substr($line, strpos($line,' ') + 1);

    if ($type == "INDEX")
    {
      //read the index number
      $number = substr($line, 0, strpos($line, ' '));
      $line = substr($line, strpos($line,' ') + 1);
    }

    //extract the minutes, seconds, and frames
    $index = explode(':', $line);

    if ($type == "INDEX")
    {
      $this->cuesheet["TRACKS"][$track_on]["INDEX"][$number] = array('MINUTES'=>$index[0], 'SECONDS'=>$index[1],'FRAMES'=>$index[2]);
    }
    else if ($type == "PREGAP")
    {
      $this->cuesheet["TRACKS"][$track_on]["PREGAP"] = array('MINUTES'=>$index[0], 'SECONDS'=>$index[1],'FRAMES'=>$index[2]);
    }
    else if ($type == "POSTGAP")
    {
      $this->cuesheet["TRACKS"][$track_on]["POSTGAP"] = array('MINUTES'=>$index[0], 'SECONDS'=>$index[1],'FRAMES'=>$index[2]);
    }
  }

  function parseString($line, $track_on)
  {
    $category = substr($line, 0, strpos($line,' '));
    $line = substr($line, strpos($line,' ') + 1);

    //get rid of the quotes
    $line = trim($line, '"');

    switch (strtoupper($category))
    {
      case "CATALOG":
      case "CDTEXTFILE":
      case "ISRC":
      case "PERFORMER":
      case "SONGWRITER":
      case "TITLE":
        if ($track_on == -1)
        {
          $this->cuesheet[$category] = $line;
        }
        else
        {
          $this->cuesheet["TRACKS"][$track_on][$category] = $line;
        }
        break;
      default:
        break;
    }
  }

  /**
   * Parses the TRACK command.
   *
   * @param string $line - The line in the cue file that contains the TRACK command.
   * @param integer $track_on - The track currently processing.
   */
  function parseTrack($line, $track_on)
  {
    $line = substr($line, strpos($line,' ') + 1);
    $track = ltrim(substr($line, 0, strpos($line,' ')),'0');

    //find the data type.
    $datatype = substr($line, strpos($line,' ') + 1);

    $this->cuesheet["TRACKS"][$track_on] = array('TRACK_NUMBER'=>$track, 'DATATYPE'=>$datatype);
  }

}

/**************************************************************************************************
 End of file
 **************************************************************************************************/
?>
