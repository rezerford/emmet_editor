<?php
/**
 * @copyright	
 * @license		
 */

// no direct access
defined('_JEXEC') or die;

/**
 * CodeMirror Editor Plugin.
 *
 * @package		Joomla.Plugin
 * @subpackage	Editors.codemirror
 * @since		1.6
 */
class plgEditorEmmet extends JPlugin
{
	/**
	 * Base path for editor files
	 */
	protected $_basePath = 'plugins/editors/emmet/js/';

	/**
	 * Initialises the Editor.
	 *
	 * @return	string	JavaScript Initialization string.
	 */
	public function onInit()
	{
		JHtml::_('core');
		JHtml::_('script', $this->_basePath . 'codemirror/codemirror.js', 	false, false, false, false);
		JHtml::_('script', $this->_basePath . 'codemirror/xml.js', 			false, false, false, false);
		JHtml::_('script', $this->_basePath . 'codemirror/css.js', 			false, false, false, false);
		JHtml::_('script', $this->_basePath . 'codemirror/javascript.js', 	false, false, false, false);
		JHtml::_('script', $this->_basePath . 'codemirror/clike.js', 		false, false, false, false);
		JHtml::_('script', $this->_basePath . 'codemirror/php.js', 			false, false, false, false);
		JHtml::_('script', $this->_basePath . 'codemirror/htmlmixed.js', 	false, false, false, false);
		JHtml::_('script', $this->_basePath . 'emmet.min.js', 				false, false, false, false);
		JHtml::_('script', $this->_basePath . 'basefiles-uncompressed.js', 	false, false, false, false);

		JHtml::_('stylesheet', $this->_basePath . 'codemirror/codemirror.css');

		return '';
	}

	/**
	 * Copy editor content to form field.
	 *
	 * @param	string	$id	The id of the editor field.
	 *
	 * @return string Javascript
	 */
	public function onSave($id)
	{
		return "document.getElementById('$id').value = Joomla.editors.instances['$id'].getCode();\n";
	}

	/**
	 * Get the editor content.
	 *
	 * @param	string	$id	The id of the editor field.
	 *
	 * @return string Javascript
	 */
	public function onGetContent($id)
	{
		return "Joomla.editors.instances['$id'].getCode();\n";
	}

	/**
	 * Set the editor content.
	 *
	 * @param	string	$id			The id of the editor field.
	 * @param	string	$content	The content to set.
	 *
	 * @return string Javascript
	 */
	public function onSetContent($id, $content)
	{
		return "Joomla.editors.instances['$id'].setCode($content);\n";
	}

	/**
	 * Adds the editor specific insert method.
	 *
	 * @return boolean
	 */
	public function onGetInsertMethod()
	{
		static $done = false;

		// Do this only once.
		if (!$done)
		{
			$done = true;
			$doc = JFactory::getDocument();
			$js = "\tfunction jInsertEditorText(text, editor) {
					Joomla.editors.instances[editor].replaceSelection(text);\n
			}";
			$doc->addScriptDeclaration($js);
		}

		return true;
	}

	/**
	 * Display the editor area.
	 *
	 * @param	string	$name		The control name.
	 * @param	string	$html		The contents of the text area.
	 * @param	string	$width		The width of the text area (px or %).
	 * @param	string	$height		The height of the text area (px or %).
	 * @param	int		$col		The number of columns for the textarea.
	 * @param	int		$row		The number of rows for the textarea.
	 * @param	boolean	$buttons	True and the editor buttons will be displayed.
	 * @param	string	$id			An optional ID for the textarea (note: since 1.6). If not supplied the name is used.
	 * @param	string	$asset
	 * @param	object	$author
	 * @param	array	$params		Associative array of editor parameters.
	 *
	 * @return string HTML
	 */
	public function onDisplay($name, $content, $width, $height, $col, $row, $buttons = true, $id = null, $asset = null, $author = null, $params = array())
	{
		if (empty($id)) {
			$id = $name;
		}

		// Only add "px" to width and height if they are not given as a percentage
		if (is_numeric($width)) {
			$width .= 'px';
		}

		if (is_numeric($height)) {
			$height .= 'px';
		}

		// Must pass the field id to the buttons in this editor.
		$buttons = $this->_displayButtons($id, $buttons, $asset, $author);


		$options	= new stdClass;

		$options->height		= $height;
		$options->width			= $width;
		$options->continuousScanning = 500;
		$options->lineNumbers	= true;
		$options->lineWrapping	= true;
		$options->tabMode 		= 'shift';
		$options->mode 			= "text/html";
		$options->profile 		= "html";
		
		$keys = new stdClass;
		$keys->F11	= "function(cm) { setFullScreen(cm, !isFullScreen(cm));}";
		$keys->Esc	= "function(cm) { if (isFullScreen(cm)) setFullScreen(cm, false);}";
		
		$options->extraKeys		= $keys;
		
		$str_options = json_encode($options);
		$str_options = preg_replace('/\"(function)/', "$1", $str_options);
		$str_options = preg_replace('/(\})\"/', "$1", $str_options);
		
		$html = array();
		$html[]	= "<div class='cm-wrapper' style='width:".$width.";height:".$height.";'>";
        $html[] = '<div class="cm-panel"><a class="cm-button hint-button modal_jform_created_by" title="Show Help" href="../'. $this->_basePath .'help.html" rel="{handler: \'iframe\', size: {x: 800, y: 500}}">Help</a></div>';
        $html[] = "<textarea name=\"$name\" id=\"$id\" cols=\"$col\" rows=\"$row\">$content</textarea>";
        $html[] = '</div>';
		$html[] = '<script type="text/javascript">';
		$html[] = '(function() {';
		$html[] = 'var editor = CodeMirror.fromTextArea(document.getElementById("'.$id.'"), '.$str_options.');';
		$html[] = 'Joomla.editors.instances[\''.$id.'\'] = editor;';
		
		//Override Joomla.submitbutton
		$html[] = 'Joomla.submitbutton = function(task) {';
		$html[] = 'if (task.match(/\.cancel/) || document.formvalidator.isValid(document.adminForm)) {';
		$html[] = 'Joomla.submitform(task, document.adminForm);';
		$html[] = '} else {';
		$html[] = 'alert(\'Invalid form\');';
		$html[] = '}';
		$html[] = '}';
		
		//Override insertReadmore
		$html[] = 'insertReadmore = function(editor) {';
        $html[] = 'var content = document.getElementById(editor).value;';
        $html[] = 'if (content.match(/<hr\s+id=("|\')system-readmore("|\')\s*\/*>/i)) {';
        $html[] = 'alert(\'There is already a Read more... link that has been inserted. Only one such link is permitted. Use {pagebreak} to split the page up further.\');';
        $html[] = 'return false;';
        $html[] = '} else {';
        $html[] = 'jInsertEditorText(\'<hr id="system-readmore" />\', editor);';
        $html[] = '}';
        $html[] = '}';
		
		$html[] = '})()';
		$html[] = '</script>';
		$html[] = $buttons;
		
		return implode("\n", $html);
	}

	/**
	 * Displays the editor buttons.
	 *
	 * @param string $name
	 * @param mixed $buttons [array with button objects | boolean true to display buttons]
	 *
	 * @return string HTML
	 */
	protected function _displayButtons($name, $buttons, $asset, $author)
	{
		// Load modal popup behavior
		JHtml::_('behavior.modal', 'a.modal-button');

		$args['name'] = $name;
		$args['event'] = 'onGetInsertMethod';

		$html = array();
		$results[] = $this->update($args);

		foreach ($results as $result)
		{
			if (is_string($result) && trim($result)) {
				$html[] = $result;
			}
		}

		if (is_array($buttons) || (is_bool($buttons) && $buttons)) {
			$results = $this->_subject->getButtons($name, $buttons, $asset, $author);

			// This will allow plugins to attach buttons or change the behavior on the fly using AJAX
			$html[] = '<div id="editor-xtd-buttons">';

			foreach ($results as $button)
			{
				// Results should be an object
				if ($button->get('name')) {
					$modal		= ($button->get('modal')) ? 'class="modal-button"' : null;
					$href		= ($button->get('link')) ? 'href="'.JURI::base().$button->get('link').'"' : null;
					$onclick	= ($button->get('onclick')) ? 'onclick="'.$button->get('onclick').'"' : null;
					$title      = ($button->get('title')) ? $button->get('title') : $button->get('text');
					$html[] = '<div class="button2-left"><div class="'.$button->get('name').'">';
					$html[] = '<a '.$modal.' title="'.$title.'" '.$href.' '.$onclick.' rel="'.$button->get('options').'">';
					$html[] = $button->get('text').'</a></div></div>';
				}
			}

			$html[] = '</div>';
		}

		return implode("\n", $html);
	}
}
