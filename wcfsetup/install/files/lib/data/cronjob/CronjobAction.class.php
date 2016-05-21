<?php
namespace wcf\data\cronjob;
use wcf\data\cronjob\log\CronjobLogEditor;
use wcf\data\user\User;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\IToggleAction;
use wcf\system\cronjob\CronjobScheduler;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;
use wcf\util\DateUtil;

/**
 * Executes cronjob-related actions.
 * 
 * @author	Tim Duesterhus, Alexander Ebert
 * @copyright	2001-2016 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	data.cronjob
 * @category	Community Framework
 */
class CronjobAction extends AbstractDatabaseObjectAction implements IToggleAction {
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\cronjob\CronjobEditor';
	
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::$permissionsCreate
	 */
	protected $permissionsCreate = ['admin.management.canManageCronjob'];
	
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::$permissionsDelete
	 */
	protected $permissionsDelete = ['admin.management.canManageCronjob'];
	
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::$permissionsUpdate
	 */
	protected $permissionsUpdate = ['admin.management.canManageCronjob'];
	
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::$allowGuestAccess
	 */
	protected $allowGuestAccess = ['executeCronjobs'];
	
	/**
	 * @see	\wcf\data\AbstractDatabaseObjectAction::$requireACP
	 */
	protected $requireACP = ['create', 'delete', 'update', 'toggle', 'execute'];
	
	/**
	 * @see	\wcf\data\IDeleteAction::validateDelete()
	 */
	public function validateDelete() {
		parent::validateDelete();
		
		foreach ($this->objects as $cronjob) {
			if (!$cronjob->isDeletable()) {
				throw new PermissionDeniedException();
			}
		}
	}
	
	/**
	 * @see	\wcf\data\AbstractDatabaseAction::validateUpdate()
	 */
	public function validateUpdate() {
		parent::validateUpdate();
		
		foreach ($this->objects as $cronjob) {
			if (!$cronjob->isEditable()) {
				throw new PermissionDeniedException();
			}
		}
	}
	
	/**
	 * @see	\wcf\data\IToggleAction::validateToggle()
	 */
	public function validateToggle() {
		parent::validateUpdate();
		
		foreach ($this->objects as $cronjob) {
			if (!$cronjob->canBeDisabled()) {
				throw new PermissionDeniedException();
			}
		}
	}
	
	/**
	 * @see	\wcf\data\IToggleAction::toggle()
	 */
	public function toggle() {
		foreach ($this->objects as $cronjob) {
			$cronjob->update([
				'isDisabled' => $cronjob->isDisabled ? 0 : 1
			]);
		}
	}
	
	/**
	 * Validates the 'execute' action.
	 */
	public function validateExecute() {
		parent::validateUpdate();
	}
	
	/**
	 * Executes cronjobs.
	 */
	public function execute() {
		$return = [];
		
		foreach ($this->objects as $key => $cronjob) {
			// mark them as pending
			$cronjob->update(['state' => Cronjob::PENDING]);
		}
		
		foreach ($this->objects as $cronjob) {
			// it now time for executing
			$cronjob->update(['state' => Cronjob::EXECUTING]);
			$className = $cronjob->className;
			$executable = new $className();
			
			// execute cronjob
			$exception = null;
			
			// check if all required options are set for cronjob to be executed
			// note: a general log is created to avoid confusion why a cronjob
			// apperently is not executed while that is indeed the correct internal
			// behavior
			if ($cronjob->validateOptions()) {
				try {
					$executable->execute(new Cronjob($cronjob->cronjobID));
				}
				catch (\Exception $exception) { }
			}
			
			CronjobLogEditor::create([
				'cronjobID' => $cronjob->cronjobID,
				'execTime' => TIME_NOW,
				'success' => ($exception ? 0 : 1),
				'error' => ($exception ? $exception->getMessage() : '')
			]);
			
			// calculate next exec-time
			$nextExec = $cronjob->getNextExec();
			$data = [
				'lastExec' => TIME_NOW,
				'nextExec' => $nextExec, 
				'afterNextExec' => $cronjob->getNextExec(($nextExec + 120))
			];
			
			// cronjob failed
			if ($exception) {
				if ($cronjob->failCount < Cronjob::MAX_FAIL_COUNT) {
					$data['failCount'] = $cronjob->failCount + 1;
				}
				
				// cronjob failed too often: disable it
				if ($cronjob->failCount + 1 == Cronjob::MAX_FAIL_COUNT) {
					$data['isDisabled'] = 1;
				}
			}
			// if no error: reset fail counter
			else {
				$data['failCount'] = 0;
				
				// if cronjob has been disabled because of too many
				// failed executions, enable it again
				if ($cronjob->failCount == Cronjob::MAX_FAIL_COUNT && $cronjob->isDisabled) {
					$data['isDisabled'] = 0;
				}
			}
			
			$cronjob->update($data);
			
			// build the return value
			if ($exception === null && !$cronjob->isDisabled) {
				$dateTime = DateUtil::getDateTimeByTimestamp($nextExec);
				$return[$cronjob->cronjobID] = [
					'time' => $nextExec,
					'formatted' => str_replace(
						'%time%', 
						DateUtil::format($dateTime, DateUtil::TIME_FORMAT), 
						str_replace(
							'%date%', 
							DateUtil::format($dateTime, DateUtil::DATE_FORMAT), 
							WCF::getLanguage()->get('wcf.date.dateTimeFormat')
						)
					)
				];
			}
			
			// we are finished
			$cronjob->update(['state' => Cronjob::READY]);
			
			// throw exception again to show error message
			if ($exception) {
				throw $exception;
			}
		}
		
		return $return;
	}
	
	/**
	 * Validates the 'executeCronjobs' action.
	 */
	public function validateExecuteCronjobs() {
		// does nothing
	}
	
	/**
	 * Executes open cronjobs.
	 */
	public function executeCronjobs() {
		// switch session owner to 'system' during execution of cronjobs
		WCF::getSession()->changeUser(new User(null, ['userID' => 0, 'username' => 'System']), true);
		WCF::getSession()->disableUpdate();
		
		CronjobScheduler::getInstance()->executeCronjobs();
	}
}
