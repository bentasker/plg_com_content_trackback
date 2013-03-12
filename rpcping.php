<?php
/**
*
* Joomla 2.5 Adaptation of the K2 Item Ordering Plugin by www.jiliko.net
*
* @author B Tasker
* @license GNU GPL V2 - See http://www.gnu.org/licenses/gpl-2.0.html
* @Copyright (C) 2013 Virya Technologies - Original (C) Jiliko,net
* 
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Init the plugin
jimport( 'joomla.plugin.plugin' );
JLoader::register('K2Plugin',JPATH_ADMINISTRATOR.DS.'components'.DS.'com_k2'.DS.'lib'.DS.'k2plugin.php');
JTable::addIncludePath(JPATH_COMPONENT.DS.'tables');



class plgK2ItemOrdering extends K2Plugin {

	// Some params
	var $pluginName = 'itemordering';
	var $pluginNameHumanReadable = 'K2 item ordering';
	var $params = null;
	
	function plgK2ItemOrdering(&$subject, $params) {
		parent::__construct($subject, $params);
		
		$plugin = & JPluginHelper::getPlugin('k2', $this->pluginName);
		
		// This was deprecated in 2.5 and doesn't exist in 3
		//$params = new JParameter($plugin->params);
		$params = new JRegistry($plugin->params);
	}


	function onAfterK2Save( & $item, $isNew) {
		
		
		if ($isNew) {


			  // If category specific settings have been made, obey!
		      if(($this->params->get('applyTo') == 0) && (!in_array($item->catid,$this->params->get('applCategories')))){
			return '';
		      }


		
	  


			$db = & JFactory::getDBO();
			
			$query = "SELECT MIN(ordering) FROM #__k2_items WHERE catid={$item->catid} AND trash=0 AND ordering > 0";
			$db->setQuery($query);
			
			$minOrdering = $db->loadResult();
			$item->ordering = $minOrdering -1;
	
			if ($item->featured == 1){
			$forder = $this->params->get('forder');
			$item->featured_ordering = $forder;
			}


			$item->store();
			      // No idea why you wouldn't want this enabled, but I'll leave it as an option
			if ($this->params->get('initItemIds')) {
				//$query = "SELECT id FROM #__k2_items WHERE catid={$item->catid} AND trash=0 ORDER BY ordering ASC";
				$query = "UPDATE #__k2_items SET ordering=ordering+1 WHERE catid={$item->catid} AND trash=0";

				$db->setQuery($query);
				$db->query();
				
				if ($item->featured == 1){
				    $query = "UPDATE #__k2_items SET featured_ordering=featured_ordering+1 WHERE catid={$item->catid} AND featured_ordering > $forder AND featured=1";

				    $db->setQuery($query);
				    $db->query();
				}


				
				//$itemIds = $db->loadResultArray();
				
				//$item = &JTable::getInstance('K2Item', 'Table');
				
				/*foreach ($itemIds as $key => $itemId) {
					$item->load($itemId);
					$item->ordering = $key+1;
					$item->store();
				}*/
			}
		}
		
		return '';
	}
	
}