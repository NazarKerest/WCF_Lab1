<?php
namespace wcf\system\template;
use wcf\system\io\AtomicWriter;

/**
 * Compiles template source into valid PHP code.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	system.template
 * @category	Community Framework
 */
class TemplateCompiler extends TemplateScriptingCompiler {
	/**
	 * Compiles the source of a template.
	 * 
	 * @param	string		$templateName
	 * @param	string		$sourceContent
	 * @param	string		$compiledFilename
	 * @param	array		$metaData
	 */
	public function compile($templateName, $sourceContent, $compiledFilename, $metaData) {
		$writer = new AtomicWriter($compiledFilename);
		// build fileheader for template
		$writer->write("<?php\n/**\n * WoltLab Community Framework\n * Template: ".$templateName."\n * Compiled at: ".gmdate('r')."\n * \n * DO NOT EDIT THIS FILE\n */\n\$this->v['tpl']['template'] = '".addcslashes($templateName, "'\\")."';\n?>\n");
		
		// include plug-ins
		$compiledContent = $this->compileString($templateName, $sourceContent, $metaData);
		$writer->write($compiledContent['template']);
		
		// write meta data to file
		$this->saveMetaData($templateName, $metaData['filename'], $compiledContent['meta']);
		
		$writer->flush();
		$writer->close();
	}
	
	/**
	 * Saves meta data for given template.
	 * 
	 * @param	string		$templateName
	 * @param	string		$filename
	 * @param	string		$content
	 */
	public function saveMetaData($templateName, $filename, $content) {
		$writer = new AtomicWriter($filename);
		$writer->write("<?php exit; /* meta data for template: ".$templateName." (generated at ".gmdate('r').") DO NOT EDIT THIS FILE */ ?>\n");
		$writer->write(serialize($content));
		$writer->flush();
		$writer->close();
	}
	
	/**
	 * Returns the name of the current template.
	 * 
	 * @return	string
	 */
	public function getCurrentTemplate() {
		return $this->getCurrentIdentifier();
	}
}
