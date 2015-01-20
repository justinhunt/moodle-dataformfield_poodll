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
 * @subpackage poodll
 * @copyright 2013 Justin Hunt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

#require_once("$CFG->dirroot/mod/dataform/field/renderer.php");
require_once($CFG->dirroot . '/filter/poodll/poodllresourcelib.php');

/**
 *
 */
class dataformfield_poodll_renderer extends mod_dataform\pluginbase\dataformfieldrenderer {


	  /**
     *
     */
    protected function replacements(array $patterns, $entry, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->name;
        $edit = !empty($options['edit']);

        $replacements = array();

        if ($edit) {
            $firstinput = false;
            foreach ($patterns as $pattern => $cleanpattern) {
                $noedit = $this->is_noedit($pattern);
                if (!$firstinput and !$noedit and $cleanpattern == "[[$fieldname]]") {
                    $required = $this->is_required($pattern);
                    $replacements[$pattern] = array(array($this, 'display_edit'), array($entry, array('required' => $required)));
                    $firstinput = true;
                } else {
                    $replacements[$pattern] = '';
                }
            }
            return $replacements;
        }

        // Browse mode
        foreach ($patterns as $pattern => $cleanpattern) {
            $displaybrowse = '';
            if ($cleanpattern == "[[$fieldname]]") {
                $displaybrowse = $this->display_browse($entry);
            } else if ($cleanpattern == "[[{$fieldname}:url]]") {
                // url
                $displaybrowse = $this->display_browse($entry, array('url' => 1));
            } else if ($cleanpattern == "[[{$fieldname}:alt]]") {
                // alt
                $displaybrowse = $this->display_browse($entry, array('alt' => 1));
            } else if ($cleanpattern == "[[{$fieldname}:size]]") {
                // size
                $displaybrowse = $this->display_browse($entry, array('size' => 1));
            } else if ($cleanpattern == "[[{$fieldname}:download]]") {
                // download
                $displaybrowse = $this->display_browse($entry, array('download' => 1));
            } else if ($cleanpattern == "[[{$fieldname}:downloadcount]]") {
                // download count
                $displaybrowse = $this->display_browse($entry, array('downloadcount' => 1));
            }

            if (!empty($displaybrowse)) {
                $replacements[$pattern] = $displaybrowse;
            } else {
                $replacements[$pattern] = '';
            }
        }

        return $replacements;
    }


	    /**
     *
     */
    public function display_browse($entry, $params = null, $hidden = false) {

        $field = $this->_field;
        $fieldid = $field->id;
        $entryid = $entry->id;

        $content = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $content1 = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : null;
        $content2 = isset($entry->{"c{$fieldid}_content2"}) ? $entry->{"c{$fieldid}_content2"} : null;
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        
        if (empty($content)) {
            return '';
        }
		
		//I think this is meanngless in the PoodLL field. But just in case ...
        if (!empty($params['downloadcount'])) {
            return $content2;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($field->get_df()->context->id, 'mod_dataform', 'content', $contentid);
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
				//we only want to display the most recent file.
				//echo $entryid .':::'. $filename . ":" . $content . '<br/>';
				if($filename==$content){
					$filenameinfo = pathinfo($filename);
					$path = "/{$field->get_df()->context->id}/mod_dataform/content/$contentid";
					$strfiles[] = $this->display_file($file, $path, $altname, $params);
				}
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
        $fieldid = $field->id;

        $entryid = $entry->id;
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        $content = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $content1 = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : null;

        $fieldname = "field_{$fieldid}_{$entryid}";
        $fmoptions = array('subdirs' => 0,
                            'maxbytes' =>$field->param1,
                            'maxfiles' =>$field->param2,
                            'accepted_types' => array($field->param3));

        $draftitemid = file_get_submitted_draft_itemid("{$fieldname}_" . DF_DRAFTIDCONTROL);
        file_prepare_draft_area($draftitemid, $field->get_df()->context->id, 'mod_dataform', 'content', $contentid, $fmoptions);
    	$usercontext = context_user::instance($USER->id);
		$usercontextid=  $usercontext->id;
		
		$contextid =  $field->get_df()->context->id;
		$recstring="";
		
		//Set the control that will get notified about the recorded file's name
		$updatecontrol=$fieldname . "_" .  DF_FILENAMECONTROL;
		$vectorcontrol=$fieldname . "_" . DF_VECTORCONTROL;
		$mform->addElement('hidden', $updatecontrol, $content,array('id' => $updatecontrol));
		$mform->addElement('hidden', $vectorcontrol, $content1,array('id' => $vectorcontrol));
		$mform->addElement('hidden', "{$fieldname}_" . DF_DRAFTIDCONTROL, $draftitemid);
		$mform->setType($updatecontrol, PARAM_TEXT); 
		$mform->setType($vectorcontrol, PARAM_TEXT);
		$mform->setType("{$fieldname}_" . DF_DRAFTIDCONTROL, PARAM_TEXT); 
		$vectordata=$content1;
		

		 switch ($field->{DF_FIELD_RECTYPE}){
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
				switch($field->{DF_FIELD_BOARDSIZE}){
					case "320x320": $width=320;$height=320;break;
					case "400x600": $width=400;$height=600;break;
					case "500x500": $width=500;$height=500;break;
					case "600x400": $width=600;$height=400;break;
					case "600x800": $width=600;$height=800;break;
					case "800x600": $width=800;$height=600;break;
				}

				
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
				//since file upload fails, we use the external link way
				if(empty($imageurl)){
					$imageurl = $field->{DF_POODLLFIELD_BACKIMAGE_URL};
				}
        		$recstring  .= fetchWhiteboardForSubmission($updatecontrol,$usercontextid,"user","draft",$draftitemid,$width, $height, $imageurl,"",false, $vectorcontrol,$vectordata);
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

			switch ($field->{DF_FIELD_RECTYPE}){
				case DF_REPLYSNAPSHOT:
				case DF_REPLYWHITEBOARD:
					if ($file->is_valid_image()) {
						 $imgattr = array();
						 $imgattr['src'] = $filepath;
						 $imgattr['alt'] = $filename;
						 if($field->{DF_POODLLFIELD_WIDTH} || $field->{DF_POODLLFIELD_HEIGHT}){
							$imgattr['style'] = 'width: ' . $field->{DF_POODLLFIELD_WIDTH} . 'px; ' . 'height: ' . $field->{DF_POODLLFIELD_HEIGHT} . 'px; ';
						 }
						 $str = html_writer::empty_tag('img', $imgattr);
						 return $str;
					}else{
						return '';
					}
					break;
				case DF_REPLYVOICE:
				case DF_REPLYVIDEO:
				case DF_REPLYMP3VOICE:
					//this will occur if we have poster/splash image
					//we don't want to dislay it inline
					if ($file->is_valid_image()) {
						return '';
					}
					if($field->{DF_POODLLFIELD_URLONLY}){
						return $filepath;
					}else{
					   $dim = "";	
						if($field->{DF_POODLLFIELD_WIDTH} || $field->{DF_POODLLFIELD_HEIGHT}){
							$dim = '?d=' . $field->{DF_POODLLFIELD_WIDTH}. 'x' . $field->{DF_POODLLFIELD_HEIGHT};
						}
						//this will format the player using whatever filter the admin has enabled
						return format_text("<a href='$filepath" . $dim . "'>$filename</a>",FORMAT_HTML);
					}
					break;
				default:
					return format_text("<a href='$filepath'>$filename</a>",FORMAT_HTML);
			}

			
		}else{
			return "";
		}
    }
    
      
     public function pluginfile_patterns() {
         return array("[[{$this->_field->name}]]");
     }
   
      /**
      * Array of patterns this field supports 
      */
     protected function patterns() {
         $fieldname = $this->_field->name;
 
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
