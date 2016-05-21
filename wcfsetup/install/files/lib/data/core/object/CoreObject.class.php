<?php
namespace wcf\data\core\object;
use wcf\data\DatabaseObject;

/**
 * Represents a core object.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	data.core.object
 * @category	Community Framework
 * 
 * @property-read	integer		$objectID
 * @property-read	integer		$packageID
 * @property-read	string		$objectName
 */
class CoreObject extends DatabaseObject {
	/**
	 * @see	\wcf\data\DatabaseObject::$databaseTableName
	 */
	protected static $databaseTableName = 'core_object';
	
	/**
	 * @see	\wcf\data\DatabaseObject::$databaseTableIndexName
	 */
	protected static $databaseTableIndexName = 'objectID';
}
