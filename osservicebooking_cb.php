<?php
/**
 * @package OSServiceBooking for CB
 * @version 2.1.6 for OSServiceBooking v2.1 & CB v2.0
 * @author Hector Garzo
 * @copyright (C) 2013-2016 mygroup.net
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2
 *
 *
 */

defined('_JEXEC') or die;

JHTML::_('behavior.modal');


class osservicebookingTab extends cbTabHandler {

	protected $_bookingFound = true;

	// does #__jem_register table has a column 'state'
	protected $_found_state_field = false;

	/* JEM Attending tab
	 */
	function __construct()
	{
		global $_CB_database;

		// Check if JEM is installed.
		$this->_bookingFound = class_exists('JemImage') && class_exists('JemOutput') && class_exists('JemHelperRoute');
		parent::__construct();
	}


	/**
	 * Retrieve the languagefile
	 * The file is located in the folder language
	 */
	function _getLanguageFile()
	{
		global $_CB_framework;

		$lang = JFactory::getLanguage();
		$lang->load('com_osservicebooking', JPATH_BASE.'/components/com_osservicebooking');

		$UElanguagePath = dirname(__FILE__);
		if (file_exists($UElanguagePath.'/language/'.$_CB_framework->getCfg('lang').'.php')) {
			include_once($UElanguagePath.'/language/'.$_CB_framework->getCfg('lang').'.php');
		} else {
			include_once($UElanguagePath.'/language/english.php');
		}
	}

	/**
	 * Check (asking) user's permissions.
	 */
	protected function _checkPermission($user, $juser)
	{
		// $user is profile's owner whilst $juser is asking user

		if ($juser->id != $user->id) {
			// we have to check if foreign announces are allowed to show
			$permitted = false;
			
			//$settings = JEMHelper::globalattribs();

			$permitted = $juser->authorise('core.manage', 'com_osservicebooking');

			/*switch ($settings->get('event_show_attendeenames', 2)) {
				case 0: // show to none
				default:
					break;
				case 1: // show to admins
					$permitted = $juser->authorise('core.manage', 'com_osservicebooking');
					break;
				case 2: // show to registered
					$permitted = !$juser->get('guest', 1);
					break;
				case 3: // show to all
					$permitted = true;
					break;
			}*/

		} else {
			$permitted = true;
		}

		return true;//$permitted;
	}

	/**
	 * Creates and returns the sql query.
	 */
	protected function _getQuery($user, $fast = false)
	{
		$userid = $user->id;

		// Support Joomla access levels instead of single group id
		// Note: $user is one which profile is requested, not the asking user!
		//       $juser is the asking user which view access levels must be used.
		$juser  = JFactory::getUser();
		$levels = $juser->getAuthorisedViewLevels();

		$query      = NULL;

		return $query;
	}


	/**
	 * Append number of events to the title.
	 *
	 * since CB 2.0, on CB 1.9 it's simply not called
	 */
	public function getTabTitle($tab, $user, $ui, $postdata)
	{
		/* loading global variables */
		global $_CB_database;

		$total = 0;
		$juser = JFactory::getUser();

		$userId = (int) JFactory::getUser()->get('id');

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('count(distinct plan_id)')
			->from('#__osmembership_subscribers')
			->where('user_id = ' . (int) $userId);

		$db->setQuery($query);
		$total = $_CB_database->loadResult();

		return parent::getTabTitle($tab, $user, $ui, $postdata) . ' <span class="badge badge-default">' . (int)$total . '</span>';
	}

	/**
	 * Display Tab
	 */
	function getDisplayTab($tab, $user, $ui)
	{
		/* loading global variables */
		global $_CB_database, $_CB_framework;

		/* loading the language function */
		self::_getLanguageFile();

		/*loading params set by the backend*/
		$params = $this->params;

		/* message at the bottom of the table */
		$event_tab_message = $params->get('hwTabMessage', "");

		/* other variables */
		$return = null;

		/* access rights check */
		// $user is profile's owner but we need logged-in user here
		$juser = JFactory::getUser();

		if (!$this->_checkPermission($user, $juser)) {
			return ''; // which will completely hide the tab
		}

		if (!empty($tab->description)) {
			// html content is allowed in descriptions
			//$return .= "\t\t<div class=\"tab_Description\">". $tab->description . "</div>\n";
		}

		// Support Joomla access levels instead of single group id; $juser is the asking user
		$levels = $juser->getAuthorisedViewLevels();
		
		
		if ($juser->id != $user->id) 
				$subscriptions = $this->getSubscriptions($user->id);
		else
				$subscriptions = $this->getSubscriptions();

		$return .= '<table class="bookingsContainer table table-hover table-responsive">';
		$return .= '	<thead>';
		$return .= '	<tr>';
		$return .= '		<th>';
		$return .= 			OSM_PLAN ;
		$return .= '		</th>';
		$return .= '		<th class="center">';
		$return .= 			OSM_START ;
		$return .= '		</th>';
		$return .= '		<th class="center">';
		$return .= 			OSM_END ;
		$return .= '		</th>';
		$return .= '		<th class="center">';
		$return .= 			 OSM_TIME_EXPIRE;
		$return .= '		</th>';
		$return .= '		<th class="center">';
		$return .= 			OSM_QUOTAS_AVAIBLE ;
		$return .= '		</th>';
		$return .= '		<th class="center">';
		$return .= 			OSM_QUOTAS_CONSUMED ;
		$return .= '		</th>';
		$return .= '		<th class="center">';
		$return .= 			OSM_SUBSCRIPTION_STATUS; 
		$return .= '		</th>';
		$return .= '	</tr>';
		$return .= '	</thead>';
		$return .= '	<tbody>';

		foreach($subscriptions as $subscription)
		{
			$return .= '<tr>';

			$return .= '	<td>';
			$return .= 		$subscription->title;
			$return .= '	</td>';

			$return .= '	<td class="center">';
			$return .= 		JHtml::_('date', $subscription->subscription_from_date, 'd-m-Y H:m:s', null); 
			$return .= '	</td>';

			$return .= '	<td class="center">';
			$return .= 		JHtml::_('date', $subscription->subscription_to_date, 'd-m-Y H:m:s', null); 
			$return .= '	</td>';

			$return .= '	<td class="center"><span class="badge">';
			if ($subscription->lifetime_membership || $subscription->subscription_to_date  == '2099-12-31 23:59:59')
				$return .= OSM_NEVER_EXPIRE;
			else
				$return .= 		$subscription->expire_in;
			$return .= '	</span></td>';

			$return .= '	<td class="center"><span class="badge">';
			$return .= 		$subscription->subscription_quotas;
			$return .= '	</span></td>';
      $url  = 'index.php?option=com_osservicesbooking&task=default_information&sid='.$subscription->subscription_id.'&lang=es&tmpl=component';
      if ($juser->id != $user->id) 
      		$url  .= '&uid='.$user->id;
      $purl = JRoute::_($url);

      $return .= '	<td class="center">';
			$return .= '    <a class="modal" href="'.$purl.'" rel="{handler: \'iframe\', size: {x: 950, y: 350}}" style="display:block;position:relative;">';
			$return .= '    <span class="badge badge-info tip hasTooltip" title="" data-original-title="OSM_DETAILS_BOOKING">'.($subscription->plan_subscription_quotas > 0) ? $subscription->plan_subscription_quotas : '0'. '</span>';
			$return .= '	</a></td>';

			$return .= '	<td class="center">';
					switch ($subscription->subscription_status)
					{
						case 0 :
			$return .= OSM_PENDING;
							break ;
						case 1 :
			$return .= OSM_ACTIVE;
							break ;
						case 2 :
			$return .= OSM_EXPIRED;
							break ;
						case 5 :
			$return .= OSM_CONSUMED;
							break ;
						case 6 :
			$return .= OSM_FROZEN;
							break ;
						default:
			$return .= OSM_CANCELLED;
							break;
					}
			$return .= '	</td>';
			$return .= '</tr>';
		}

		$return .= '	</tbody>';
		$return .= '</table>';
	
return $return;
	}


	/**
	 * Get information about subscription plans of a user
	 *
	 * @param int $profileId
	 *
	 * @return array
	 */
	public function getSubscriptions($userId = 0)
	{
		if ($userId == 0)
		{
			$userId = (int) JFactory::getUser()->get('id');
		}

		if ($userId > 0)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*,s.id as subscription_id, s.published as subscription_status')
				->from('#__osmembership_subscribers s ')
				->leftJoin('#__osmembership_plans p on p.id = s.plan_id')
				->where('s.user_id = ' . (int) $userId)
				->order('s.id desc');
			$db->setQuery($query);
			$rows             = $db->loadObjectList();
			
			foreach ($rows as $row)
			{

				$row->subscription_from_date = $row->from_date;
				$row->subscription_to_date   = $row->to_date;
				
				$row->expire_in =0;
				if(!$row->lifetime_membership){
					$dStart = new DateTime($row->from_date);
					$dEnd   = new DateTime($row->to_date);

					$now   = new DateTime();
					$dDiff  = $now->diff($dEnd);

					$row->expire_in = $dDiff->days;
				}
			}
		}
		return $rows;
	}
}
?>
