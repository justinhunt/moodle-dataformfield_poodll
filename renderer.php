<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
 
/**
 * @package dataformfield
 * @subpackage file
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

define('DF_REPLYMP3VOICE',0);
define('DF_REPLYVOICE',1);
define('DF_REPLYVIDEO',2);
define('DF_REPLYWHITEBOARD',3);
define('DF_REPLYSNAPSHOT',4);
define('DF_REPLYTALKBACK',5);

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");
require_once($CFG->dirroot . '/filter/poodll/poodllresourcelib.php');

/**
 *
 */
class dataformfield_poodll_renderer extends dataformfield_renderer {


	   /**
     * 
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->name();
        $edit = !empty($options['edit']) ? $options['edit'] : false;

        $replacements = array();

        // rules support
        $tags = $this->add_clean_pattern_keys($tags);

        foreach ($tags as $tag => $cleantag) {
            if ($edit) {
                if ($cleantag == "[[$fieldname]]") {
                    $required = $this->is_required($tag);
                    $replacements[$tag] = array('', array(array($this,'display_edit'), array($entry, array('required' => $required))));
                } else {
                    $replacements[$tag] = '';
                }
            } else {
                $displaybrowse = '';
                if ($cleantag == "[[$fieldname]]") {
                    $displaybrowse = $this->display_browse($entry);
                // url    
                } else if ($cleantag == "[[{$fieldname}:url]]") {
                    $displaybrowse = $this->display_browse($entry, array('url' => 1));
                // alt
                } else if ($cleantag == "[[{$fieldname}:alt]]") {
                    $displaybrowse = $this->display_browse($entry, array('alt' => 1));
                // size
                } else if ($cleantag == "[[{$fieldname}:size]]") {
                    $displaybrowse = $this->display_browse($entry, array('size' => 1));
                // content (for html files)
                } else if ($cleantag == "[[{$fieldname}:content]]") {
                    if ($edit) {
                        $replacements[$tag] = array('', array(array($this,'display_edit_content'), array($entry)));
                    } else {
                        $displaybrowse = $this->display_browse($entry, array('content' => 1));
                    }
                // download
                } else if ($cleantag == "[[{$fieldname}:download]]") {
                    $displaybrowse = $this->display_browse($entry, array('download' => 1));
                // download count
                } else if ($cleantag == "[[{$fieldname}:downloadcount]]") {
                    $displaybrowse = $this->display_browse($entry, array('downloadcount' => 1));
                }
                
                if (!empty($displaybrowse)) {
                    if ($this->is_hidden($tag)) {
                        $displaybrowse = html_writer::tag('span', $displaybrowse, array('class' => 'hide'));
                    }
                    $replacements[$tag] = array('html', $displaybrowse);
                } else {
                    $replacements[$tag] = '';
                }
            }           
        }

        return $replacements;
    }


	    /**
     *
     */
    public function display_browse($entry, $params = null, $hidden = false) {

        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;

        $content = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $content1 = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : null;
        $content2 = isset($entry->{"c{$fieldid}_content2"}) ? $entry->{"c{$fieldid}_content2"} : null;
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        
        if (empty($content)) {
            return '';
        }

        if (!empty($params['downloadcount'])) {
            return $content2;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($field->df()->context->id, 'mod_dataform', 'content', $contentid);
        if (!$files or !(count($files) > 1)) {
            return '';
        }

        $altname = empty($content1) ? '' : s($content1);

        if (!empty($params['alt'])) {
            return $altname;
        }

        $strfiles = array();
        foreach ($files as $file) {
            if (!$file->is_directory()) {

                $filename = $file->get_filename();
                $filenameinfo = pathinfo($filename);
                $path = "/{$field->df()->context->id}/mod_dataform/content/$contentid";

                $strfiles[] = $this->display_file($file, $path, $altname, $params);
            }
        }
        return implode("<br />\n", $strfiles);
    }
	
     /**
     *
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        global $USER, $PAGE;

        $field = $this->_field;
        $fieldid = $field->id();

        $entryid = $entry->id;
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        $content = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $content1 = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : null;

        $fieldname = "field_{$fieldid}_{$entryid}";
        $fmoptions = array('subdirs' => 0,
                            'maxbytes' => $field->get('param1'),
                            'maxfiles' => $field->get('param2'),
                            'accepted_types' => array($field->get('param3')));

        $draftitemid = file_get_submitted_draft_itemid("{$fieldname}_" . DF_DRAFTIDCONTROL);
        file_prepare_draft_area($draftitemid, $field->df()->context->id, 'mod_dataform', 'content', $contentid, $fmoptions);
    	$usercontext = context_user::instance($USER->id);
		$usercontextid=  $usercontext->id;
		
		$contextid =  $field->df()->context->id;
		$recstring="";
		
		//Set the control that will get notified about the recorded file's name
		$mform->addElement('hidden', $fieldname . DF_FILENAMECONTROL, '',array('id' => $fieldname . DF_FILENAMECONTROL));
		$mform->addElement('hidden', "{$fieldname}_" . DF_DRAFTIDCONTROL, $draftitemid);
		$mform->setType($fieldname . DF_FILENAMECONTROL, PARAM_TEXT); 
		$mform->setType("{$fieldname}_" . DF_DRAFTIDCONTROL, PARAM_TEXT); 
		$updatecontrol=$fieldname . DF_FILENAMECONTROL;
		
		 switch ($field->get(DF_FIELD_RECTYPE)){
        	case DF_REPLYVOICE:
        		$recstring .= fetchAudioRecorderForSubmission('auto','ignore',$updatecontrol,$usercontextid,"user","draft",$draftitemid);
        		break;
        	
        	case DF_REPLYVIDEO:
        		$recstring  .= fetchVideoRecorderForSubmission('auto','ignore',$updatecontrol,$usercontextid,"user","draft",$draftitemid);
        		break;
        	
        	case DF_REPLYMP3VOICE:
        		$recstring .= fetchMP3RecorderForSubmission($updatecontrol,$usercontextid,"user","draft",$draftitemid);
        		break;
        	
        	case DF_REPLYWHITEBOARD:
				//the board size is the size of the drawing canvas, not the widget
				switch($field->get(DF_FIELD_BOARDSIZE)){
					case "320x320": $width=320;$height=320;break;
					case "400x600": $width=400;$height=600;break;
					case "500x500": $width=500;$height=500;break;
					case "600x400": $width=600;$height=400;break;
					case "600x800": $width=600;$height=800;break;
					case "800x600": $width=800;$height=600;break;
				}
				$width=$width + 205;
				$height=$height + 20;
				
				//Get Backimage, if we have one
				// get file system handle for fetching url to submitted media prompt (if there is one) 
				$fs = get_file_storage();
				$filearea =  DF_POODLL_CONFIG_FILEAREA;//$field->filearea();
				$itemid=$fieldid;
				$files = $fs->get_area_files($contextid, DF_POODLL_COMPONENT, 
								$filearea, 
								$itemid);
				
				$imageurl="";
				if($files && count($files)>0){

					$file = array_pop($files);
					$imageurl = file_rewrite_pluginfile_urls('@@PLUGINFILE@@/' . $file->get_filename(), 
								'pluginfile.php', 
								$file->get_contextid(), 
								$file->get_component(), 
								$file->get_filearea(), 
								$file->get_itemid());
				
				}
				error_log('imageurl: ' . $imageurl);
				
        		$recstring  .= fetchWhiteboardForSubmission($updatecontrol,$usercontextid,"user","draft",$draftitemid,$width, $height, $imageurl);
        		break;
        		
        	case DF_REPLYSNAPSHOT:

        		$recstring  .= fetchSnapshotCameraForSubmission($updatecontrol,'apic.jpg',350,400,$usercontextid,"user","draft",$draftitemid);
        		break;

		}

		$mform->addElement('static', 'description', '',$recstring);
    }

    /**
     * 
     */
    protected function display_file($file, $path, $altname, $params = null) {
        global $CFG, $OUTPUT;

		$field = $this->_field;
        $filename = $file->get_filename();
        $displayname = $altname ? $altname : $filename;
  
        if ($filename) {
             $filepath = moodle_url::make_file_url('/pluginfile.php', "$path/$filename") ;   

			//display file as a player or an image
			 if ($file->is_valid_image()) {
				 $imgattr = array();
				 $imgattr['src'] = $filepath;
				 $imgattr['alt'] = $filename;
				 $str = html_writer::empty_tag('img', $imgattr);
				 return $str;
				//return "<img alt='$filename' src='$filepath'/>";
			}else{
				return format_text("<a href='$filepath'>$filename</a>",FORMAT_HTML);
			}
			
			//preferred to do it the way below, but the $field object did not appear populated
			/*
			 switch ($field->get(DF_FIELD_RECTYPE)){
				case DF_REPLYSNAPSHOT:
				case DF_REPLYWHITEBOARD:
					 $imgattr = array();
					 $imgattr['src'] = $filepath;
					 $imgattr['alt'] = $filename;
					 $str = html_writer::empty_tag('img', $imgattr);
					 return $str;
				case DF_REPLYVOICE:
					if(stripos($filename,'.flv')){
						return fetchSimpleAudioPlayer('auto',urlencode($filepath),'http');
					}
				case DF_REPLYVIDEO:
				case DF_REPLYMP3VOICE:
				default:
					return format_text("<a href='$filepath'>$filename</a>",FORMAT_HTML);
			}
			*/
	 
		}else{
			return "";
		}
    }
    
      
     public function pluginfile_patterns() {
         return array("[[{$this->_field->name()}]]");
     }
   
      /**
      * Array of patterns this field supports 
      */
     protected function patterns() {
         $fieldname = $this->_field->name();
 
         $patterns = parent::patterns();
         $patterns["[[$fieldname]]"] = array(true);
         $patterns["[[$fieldname:url]]"] = array(false);
         $patterns["[[$fieldname:alt]]"] = array(true);
        $patterns["[[$fieldname:size]]"] = array(false);
         $patterns["[[$fieldname:content]]"] = array(false);
         $patterns["[[$fieldname:download]]"] = array(false);
         $patterns["[[$fieldname:downloadcount]]"] = array(false);
 
         return $patterns; 
     }
     
     /**
      * Array of patterns this field supports
      */
     protected function supports_rules() {
         return array(
             self::RULE_REQUIRED
         );
     }
}
