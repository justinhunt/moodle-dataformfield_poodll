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

require_once("$CFG->dirroot/mod/dataform/field/file/classes/file.php");

define('DF_POODLL_FILEAREA', 'content');
define('DF_POODLL_COMPONENT', 'mod_dataform');
define('DF_POODLL_CONFIG_COMPONENT', 'mod_dataform');
define('DF_POODLL_CONFIG_FILEAREA', 'view');
define('DF_POODLL_WB_FILEAREA', 'content');

define('DF_FILENAMECONTROL','_filename');
define('DF_VECTORCONTROL','_vectordata');
define('DF_DRAFTIDCONTROL','filemanager');

define('DF_FIELD_RECTYPE', 'param1');
define('DF_FIELD_TIMELIMIT', 'param2');
define('DF_FIELD_BACKIMAGE', 'param3');
define('DF_FIELD_BOARDSIZE', 'param4');
define('DF_FIELD_DRAFTID', 'param5');


class dataformfield_poodll_poodll extends dataformfield_file_file {

    public $type = 'poodll';
	
	 /**
     *
     */
    public function update_content($entry, array $values = null,$savenew=false) {
        global $DB, $USER;
		
		//see textarea for how to get vectordata
//print_r($entry);
//print_r($values);
        $entryid =  $entry->id;
        $fieldid = $this->id;
		$fieldname = "field_{$fieldid}_{$entryid}";

		$filemanager = $alttext = $delete = $editor = null;
        if (!empty($values)) {
            foreach ($values as $name => $value) {
                if (!empty($name) and !empty($value)) {
                    ${$name} = $value;
                }
            }
        }

		 

     
        // store uploaded files
        $draftarea =$filemanager; // isset($entry->{$fieldname . DF_DRAFTIDCONTROL}) ? $entry->{$fieldname . DF_DRAFTIDCONTROL} : null;
        $usercontext = context_user::instance($USER->id);

        $fs = get_file_storage();
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftarea);
        if (count($files)>1) {
		
			// there are files to upload so add/update content record
            $rec = new object;
            $rec->fieldid = $fieldid;
            $rec->entryid = $entryid;
            $rec->content = 1;
            $rec->content1 = $alttext;

            if (!empty($contentid)) {
                $rec->id = $contentid;
                $DB->update_record('dataform_contents', $rec);
            } else {
                $contentid = $DB->insert_record('dataform_contents', $rec);
            }
      
            // now save files
            $options = array();
            $contextid = $this->df->context->id;
            file_save_draft_area_files($filemanager, $contextid, DF_POODLL_COMPONENT, DF_POODLL_FILEAREA, $contentid, $options);
            
            $this->update_content_files($contentid);

        // user cleared files from the field
        } else if (!empty($contentid)) {
            $this->delete_content($entryid);
        }
        return true;
    }
	
	public function get_filearea($suffix = null){
		return parent::filearea($suffix);
	}
	
	public function insert_field($fromform = null) {
		$fieldid = parent::insert_field($fromform);
		if(!$fieldid){return;}
		
		$options = array();
		$contextid = $this->df->context->id;
		$contentid = $fieldid;
		$filemanager = $this->field->{DF_FIELD_BACKIMAGE};
		$filearea = DF_POODLL_CONFIG_FILEAREA;//$this->filearea();
		file_save_draft_area_files($filemanager, $contextid, DF_POODLL_COMPONENT, $filearea, $contentid, $options);
		
	}
	
	public function update_field($fromform = null) {
		$fieldupdated = parent::update_field($fromform);
		if(!$fieldupdated){return;}
		
		$options = array();
		$contextid = $this->df->context->id;
		$contentid = $this->field->id;
		$filemanager =  $this->field->{DF_FIELD_BACKIMAGE};
		$filearea =  DF_POODLL_CONFIG_FILEAREA;//$this->filearea();
		file_save_draft_area_files($filemanager, $contextid, DF_POODLL_COMPONENT, $filearea, $contentid, $options);
	}
	
 }