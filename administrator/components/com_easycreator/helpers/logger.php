<?php
/**
 * @version SVN: $Id$
 * @package    EasyCreator
 * @subpackage Helpers
 * @author     EasyJoomla {@link http://www.easy-joomla.org Easy-Joomla.org}
 * @author     Nikolai Plath (elkuku) {@link http://www.nik-it.de NiK-IT.de}
 * @author     Created on 25-May-2008
 * @license    GNU/GPL, see JROOT/LICENSE.php
 */

//-- No direct access
defined('_JEXEC') || die('=;)');

/**
 * Easy Logger.
 *
 * @package EasyCreator
 */
class EasyLogger
{
    private $fileName = '';

    private $logging = true;

    private $hot = false;

    private $fileContents = false;

    private $profile = false;

    private $profiler = null;

    private $log = array();

    private $cntCodeBoxes = 0;

    /**
     * Constructor.
     *
     * @param string $fileName Log file name
     * @param array $options Logging options
     */
    public function __construct($fileName, $options)
    {
        $this->fileName = $fileName;

        $this->logging =(in_array('logging', $options)) ? true : false;
        $this->hot =(in_array('hotlogging', $options)) ? true : false;
        $this->fileContents =(in_array('files', $options)) ? true : false;
        $this->profile =(in_array('profile', $options)) ? true : false;

        if($this->profile)
        {
            if(version_compare(JVERSION, '1.6', '<'))
            {
                //-- Load profiler for J! 1.5
                ecrLoadHelper('profiler_15');
            }
            else
            {
                //-- Load profiler for J! 1.6
                ecrLoadHelper('profiler');
            }

            $this->profiler = easyProfiler::getInstance('EasyLogger');
        }
    }//function

    /**
     * Log a string.
     *
     * @param string $string The string to log
     * @param string $error Error message
     *
     * @return void
     */
    public function log($string, $error = '')
    {
        if( ! $this->logging)
        {
            return;
        }

        $ret = '';

        if($this->profile)
        {
            $ret .= $this->profiler->mark('log');
        }

        $ret .=($error) ? '<div class="ebc_error">'.$error.'</div>' : '';
        $ret .= $string;

        $this->log[] = $ret;

        if($this->hot)
        {
            $this->writeLog();
        }
    }//function

    /**
     * Logs file write attempts.
     *
     * @param string $from Full path to template file
     * @param string $to Full path to output file
     * @param string $fileContents File contents
     * @param string $error Error message
     *
     * @return void
     */
    public function logFileWrite($from = '', $to = '', $fileContents = '', $error = '')
    {
        $noFileContents = array('ico', 'png', 'jpg', 'gif');
        $fileContents =(in_array(JFile::getExt($to), $noFileContents)) ? '' : $fileContents;

        if( ! $this->logging)
        {
            return;
        }

        if($from)
        {
            $from = str_replace(JPATH_ROOT, '', $from);
            $fromFile = JFile::getName($from);
            $from = str_replace($fromFile, '', $from);
        }

        if($to)
        {
            $to = str_replace(JPATH_ROOT, '', $to);
            $toFile = JFile::getName($to);
            $to = str_replace($toFile, '', $to);
        }

        $ret = '';
        $ret .=($this->profile) ? $this->profiler->mark('fileWrite') : '';
        $ret .= '<strong>Writing file</strong><br />';
        $ret .=($error) ? '<div class="ebc_error">'.$error.'</div>' : '';
        $ret .=($from) ? 'From: '.$from.BR.'<strong style="color: blue;">'.$fromFile.'</strong>'.BR : '';
        $ret .=($to) ? 'To:   '.$to.BR.'<strong style="color: blue;">'.$toFile.'</strong>'.BR : '';

        if($fileContents)
        {
            $ret .= '<div class="ecr_codebox_header" onclick="toggleDiv(\'ecr_codebox_'.$this->cntCodeBoxes.'\');">'
            .jgettext('File Contents').'</div>';
            $ret .= '<div id="ecr_codebox_'.$this->cntCodeBoxes.'" style="display: none;">';
            $ret .= '<div class="ebc_code">'.highlight_string($fileContents, true).'</div>';
            $ret .= '</div>';
        }

        $ret .= '<hr />';

        $this->cntCodeBoxes ++;

        $this->log[] = $ret;

        if($this->hot)
        {
            $this->writeLog();
        }
    }//function

    /**
     * Log a database query.
     *
     * @param string $query The query
     * @param string $error Error happened during execution
     *
     * @return void
     */
    public function logQuery($query, $error = false)
    {
        if( ! $this->logging)
        {
            return;
        }

        $ret = '';

        if($this->profile)
        {
            $ret = $this->profiler->mark('execute Query');
        }

        $ret .= '<strong>Executing query</strong>';

        if($error)
        {
            $ret .= '<h2 style="background-color: #ffb299;">'.jgettext('Error').'</h2>';
        }

        $ret .= '<div class="ecr_codebox_header" onclick="toggleDiv(\'ecr_codebox_'.$this->cntCodeBoxes.'\');">'
        .jgettext('Query').'</div>';

        $ret .= '<div id="ecr_codebox_'.$this->cntCodeBoxes.'" style="display: none;">';
        $ret .= '<pre class="ebc_code">'.htmlentities($query).'</pre>';
        $ret .= '</div>';
        $this->cntCodeBoxes ++;

        if($error)
        {
            $ret .= '<div class="ecr_codebox_header" onclick="toggleDiv(\'ecr_codebox_'.$this->cntCodeBoxes.'\');">'
            .jgettext('Error').'</div>';

            $ret .= '<div id="ecr_codebox_'.$this->cntCodeBoxes.'" style="display: none;">';
            $ret .= '<pre class="ebc_code">'.htmlentities($error).'</pre>';
            $ret .= '</div>';
            $this->cntCodeBoxes ++;
        }

        $ret .= '<hr />';

        $this->log[] = $ret;

        if($this->hot)
        {
            $this->writeLog();
        }
    }//function

    /**
     * Write the log to a file.
     *
     * @return boolean true on success
     */
    public function writeLog()
    {
        if( ! $this->logging)
        {
            return true;
        }

        if( ! count($this->log))
        {
            //--No log entries
            return true;
        }

        if( ! JFile::write(ECRPATH_LOGS.DS.$this->fileName, implode("\n", $this->log)))
        {
            JError::raiseWarning(100, sprintf(jgettext('The file %s could not be written to path %s')
            , $this->fileName, ECRPATH_LOGS));

            return false;
        }

        return true;
    }//function

    /**
     * Prints the log entries.
     *
     * @return string HTML log
     */
    public function printLog()
    {
        $html = '';

        if($this->logging)
        {
            $html .= '<ul>';

            foreach($this->log as $entry)
            {
                $html .= '<li>'.$entry.'</li>';
            }//foreach

            $html .= '</ul>';
        }

        return $html;
    }//function
}//class
