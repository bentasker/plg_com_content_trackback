<?php
/**
*
* Joomla 2.5 Adaptation of the K2 Item Ordering Plugin by www.jiliko.net
*
* @author B Tasker
* @license GNU GPL V2 - See http://www.gnu.org/licenses/gpl-2.0.html
* @Copyright (C) 2016 B Tasker
* @version 1.0.3
* 
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Init the plugin
jimport( 'joomla.plugin.plugin' );

class plgContentrpcping extends JPlugin{

	// Some params
	var $pluginName = 'rpcping';
	var $pluginNameHumanReadable = 'Com_Content RPC Ping functionality';
	var $params = null;
	var $cache;
	
	function plgContentrpcping(&$subject, $params) {
		parent::__construct($subject, $params);
		
		$plugin = & JPluginHelper::getPlugin('content', $this->pluginName);
		
		// This was deprecated in 2.5 and doesn't exist in 3
		//$params = new JParameter($plugin->params);
		$this->params = new JRegistry($plugin->params);

		$this->conf = JFactory::getConfig();
		    if (method_exists($this->conf,'getValue')){
		    $this->oldcachetime = $this->conf->getValue('config.cachetime');
		    $this->setfn = 'setValue';
		    $this->getfn = 'getValue';
		}else{
		    $this->oldcachetime = $this->conf->get('config.cachetime');
		    $this->setfn = 'set';
		    $this->getfn = 'get';
		}

		// Get the Cache object
		$this->cache = JFactory::getCache('plg_rpcping', 'output');

        	
	}



	/** Send the Ping
	*
	*/
	function sendPing($item){
	  
	 
	  $sitemaps = $this->params->get('sitemapURLs','');
	  $x = $this->cache->get('plg_rpcping_content',0);


	  // No sitemaps specified, do nothing!
	  if (empty($sitemaps) || $x >= $this->params->get('connectionlimit',1)){
	    return;
	  }


	  $google_enabled = $this->params->get('google',1);
	  //$ask_enabled = $this->params->get('ask',1);
	  $bing_enabled = $this->params->get('bing',1);
	  $moreover_enabled = $this->params->get('moreover',1);


	  $sitemaps = explode("\n",$sitemaps);

	  $ch = curl_init();
	  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	  curl_setopt($ch,CURLOPT_TIMEOUT,$this->params->get('connectTimeout',5));

	  foreach ($sitemaps as $sitemap){

	    if ($google_enabled){
	      // Tell Google
	      curl_setopt($ch,CURLOPT_URL,"http://www.google.com/webmasters/tools/ping?sitemap=".urlencode($sitemap));
	      curl_exec($ch);
	    }

	    if ($bing_enabled){
	      // Tell Bing
	      curl_setopt($ch,CURLOPT_URL,"http://www.bing.com/webmaster/ping.aspx?siteMap=".urlencode($sitemap));
	      curl_exec($ch);
	    }

	    /** ASK no longer offer this service
	    if ($ask_enabled){
	      // Tell Ask (anyone still use Ask??)
	      curl_setopt($ch,CURLOPT_URL,"http://submissions.ask.com/ping?sitemap=".urlencode($sitemap));
	      curl_exec($ch);
	    }*/

	    if ($moreover_enabled){
	      curl_setopt($ch,CURLOPT_URL,"http://api.moreover.com/ping?sitemap=".urlencode($sitemap));
	      curl_exec($ch);
	    }

	  }
	  $x++;
	  $this->cache->store($x, 'plg_rpcping_content');

	}
	


	/** Runs after pressing Save
	*
	* Check whether we've breached our hourly limit, and if not send a ping.
	*
	*
	*/
	function onContentAfterSave( $context,& $item, $isNew) {
		   $setfn = $this->setfn;

		  	// Set the cache time to 1 hr 
		$this->conf->$setfn('config.cachetime', 3600 );
		$this->cache->setCaching( 1 );
		
		if (!$this->params->get('pingFor',1) || $isNew){
		    // Send the Ping
		    $this->sendPing();
		}
		
		$this->conf->$setfn('config.cachetime', $this->oldcachetime );
		return '';
	}
	
}