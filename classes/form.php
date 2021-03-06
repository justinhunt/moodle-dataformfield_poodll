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
//require_once("$CFG->dirroot/mod/dataform/field/field_form.php");
//require_once("$CFG->dirroot/mod/dataform/field/file/field_form.php");

//Get our poodll resource handling lib
require_once($CFG->dirroot . '/filter/poodll/poodllresourcelib.php');
require_once('poodll.php');
//some constants for the type of online poodll assignment
/*
define('DF_REPLYMP3VOICE',0);
define('DF_REPLYVOICE',1);
define('DF_REPLYVIDEO',2);
define('DF_REPLYWHITEBOARD',3);
define('DF_REPLYSNAPSHOT',4);
*/



class dataformfield_poodll_form extends \mod_dataform\pluginbase\dataformfieldform {

      
	function field_definition(){
		global $CFG;
		
        $mform =& $this->_form;
		
        $recordertype = DF_REPLYMP3VOICE;//$this->get_config('recordertype');
		$boardsize = '400x600';//$this->get_config('boardsize');
		$backimage = '';//$this->get_config('backimage');
		$timelimit = 0;//$this->get_config('timelimit');
		
      
		//recorder options
		$recorderoptions = array();
		$recorderoptions[DF_REPLYMP3VOICE] = get_string("replymp3voice", "dataformfield_poodll");
		$recorderoptions[DF_REPLYVOICE] = get_string("replyvoice", "dataformfield_poodll");
		$recorderoptions[DF_REPLYVIDEO ] = get_string("replyvideo", "dataformfield_poodll");
		$recorderoptions[DF_REPLYWHITEBOARD ] = get_string("replywhiteboard", "dataformfield_poodll");
		$recorderoptions[DF_REPLYSNAPSHOT] = get_string("replysnapshot", "dataformfield_poodll");
		$mform->addElement('select', DF_FIELD_RECTYPE, get_string("recordertype", "dataformfield_poodll"), $recorderoptions);


		//Add a place to set a maximum recording time.
	   $mform->addElement('duration', DF_FIELD_TIMELIMIT, get_string('timelimit', 'dataformfield_poodll'));    
       $mform->setDefault(DF_FIELD_TIMELIMIT, $timelimit);
		$mform->disabledIf(DF_FIELD_TIMELIMIT, DF_FIELD_RECTYPE, 'eq', DF_REPLYWHITEBOARD);
		$mform->disabledIf(DF_FIELD_TIMELIMIT, DF_FIELD_RECTYPE, 'eq', DF_REPLYSNAPSHOT);
	  
	  //these are for the whiteboard submission
	  // added Justin 20121216 back image, and boardsizes, part of whiteboard response
		//For the back image, we 
		//(i) first have to load existing back image files into a draft area
		// (ii) add a file manager element
		//(iii) set the draft area info as the "default" value for the file manager
		$itemid = 0;
		$draftitemid = file_get_submitted_draft_itemid(DF_POODLL_WB_FILEAREA);
		$context =  false;
		//$field = $this->_field;
		//$context = $field->df()->context;

		if($context) {
			$contextid = $context->id;
		}else{
			$contextid = 0;
		}
		//disable this for now, since it doesn't work, till we can get a file area to use. 
		/*
		file_prepare_draft_area($draftitemid, $contextid, DF_POODLL_CONFIG_COMPONENT, DF_POODLL_CONFIG_FILEAREA, 
		$itemid,
		array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1));
		$mform->addElement('filemanager', DF_FIELD_BACKIMAGE, get_string('backimage', 'dataformfield_poodll'), null,array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1));
		$mform->setDefault(DF_FIELD_BACKIMAGE, $backimage);
		$mform->disabledIf(DF_FIELD_BACKIMAGE, DF_FIELD_RECTYPE, 'ne', DF_REPLYWHITEBOARD );
		*/
		//url of back image (since uploading don't work here)
		$mform->addElement('text', DF_POODLLFIELD_BACKIMAGE_URL,get_string('backimageurl', 'dataformfield_poodll'));
		$mform->setType(DF_POODLLFIELD_BACKIMAGE_URL, PARAM_TEXT);

		$boardsizes = array(
			'320x320' => '320x320',
			'400x600' => '400x600',
			'500x500' => '500x500',
			'600x400' => '600x400',
			'600x800' => '600x800',
			'800x600' => '800x600'
			);
		$mform->addElement('select', DF_FIELD_BOARDSIZE,
			get_string('boardsize', 'dataformfield_poodll'), $boardsizes);
		$mform->setDefault(DF_FIELD_BOARDSIZE, $boardsize);
		$mform->disabledIf(DF_FIELD_BOARDSIZE, DF_FIELD_RECTYPE, 'ne', DF_REPLYWHITEBOARD );

		// Max video/pic dimensions
        $grp = array();
        $grp[] = &$mform->createElement('text', DF_POODLLFIELD_WIDTH, null, array('size' => '4', 'style' => 'width:inherit;'));
        $grp[] = &$mform->createElement('text', DF_POODLLFIELD_HEIGHT, null, array('size' => '4', 'style' => 'width:inherit;'));
        $mform->addGroup($grp, 'maxpicdim', get_string('maxdimensions', 'dataformfield_picture'), ' x ', false);
        $mform->setType(DF_POODLLFIELD_WIDTH, PARAM_INT);
        $mform->setType(DF_POODLLFIELD_HEIGHT, PARAM_INT);
        $mform->addGroupRule('maxpicdim', array(DF_POODLLFIELD_WIDTH => array(array(null, 'numeric', null, 'client'))));
        $mform->addGroupRule('maxpicdim', array(DF_POODLLFIELD_HEIGHT => array(array(null, 'numeric', null, 'client'))));
        $mform->setDefault(DF_POODLLFIELD_WIDTH, 0);
        $mform->setDefault(DF_POODLLFIELD_HEIGHT, 0);
	
	}

    /**
     *
     */
	 
    function filetypes_definition() {

		$mform =& $this->_form;

        // accetped types
        $options = array();
        $options['*'] = get_string('filetypeany', 'dataform');

        $mform->addElement('select', 'param3', get_string('filetypes', 'dataform'), $options);
        $mform->setDefault('param3', '*');
    

}

}
