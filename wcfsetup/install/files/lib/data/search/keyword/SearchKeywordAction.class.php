<?php
namespace wcf\data\search\keyword;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\ISearchAction;
use wcf\system\WCF;

/**
 * Executes keyword-related actions.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	data.search.keyword
 * @category	Community Framework
 */
class SearchKeywordAction extends AbstractDatabaseObjectAction implements ISearchAction {
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\search\keyword\SearchKeywordEditor';
	
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::$allowGuestAccess
	 */
	protected $allowGuestAccess = ['getSearchResultList'];
	
	/**
	 * @see	\wcf\data\ISearchAction::validateGetSearchResultList()
	 */
	public function validateGetSearchResultList() {
		$this->readString('searchString', false, 'data');
	}
	
	/**
	 * @see	\wcf\data\ISearchAction::getSearchResultList()
	 */
	public function getSearchResultList() {
		$list = [];
		
		// find users
		$sql = "SELECT		*
			FROM		wcf".WCF_N."_search_keyword
			WHERE		keyword LIKE ?
			ORDER BY	searches DESC";
		$statement = WCF::getDB()->prepareStatement($sql, 10);
		$statement->execute([$this->parameters['data']['searchString'].'%']);
		while ($row = $statement->fetchArray()) {
			$list[] = [
				'label' => $row['keyword'],
				'objectID' => $row['keywordID']
			];
		}
		
		return $list;
	}
}
