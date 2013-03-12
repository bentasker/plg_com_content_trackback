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

class plgContentrpcping extends JPlugin

	// Some params
	var $pluginName = 'rpcping';
	var $pluginNameHumanReadable = 'Com_Content RPC Ping functionality';
	var $params = null;
	
	function __construct(&$subject, $params) {
		parent::__construct($subject, $params);
		
		$plugin = & JPluginHelper::getPlugin('content', $this->pluginName);
		
		// This was deprecated in 2.5 and doesn't exist in 3
		//$params = new JParameter($plugin->params);
		$this->params = new JRegistry($plugin->params);

		$this->conf = JFactory::getConfig();
		    if (method_exists($this->conf,'getValue')){
		    $this->oldcachetime = $this->conf->getValue('config.cachetime');
		    $this->setfn = 'setValue';
		}else{
		    $this->oldcachetime = $this->conf->get('config.cachetime')
		    $this->setfn = 'set';
		}

		// Get the Cache object
		$this->cache = JFactory::getCache('plg_rpcping', 'output');

        	
	}

	// Send the Ping
	function sendPing(){
	  $services = array(
		      "yahoo"=>"http://api.my.yahoo.com/RPC2",
		      "google"=>'http://blogsearch.google.com/ping/RPC2',
		      "technorati"=>"http://rpc.technorati.com/rpc/ping",
		      "yandex"=>"http://blogs.yandex.ru/");
		      

	  $source = JUri::current();
	  $target = JUri::current();

	  $service = 'http://blogsearch.google.com/ping/RPC2';

	  $request = xmlrpc_encode_request("pingback.ping", array($source, $target));
	  $context = stream_context_create(array('http' => array(
					    'method' => "POST",
					    'header' => "Content-Type: text/xml",
					    'content' => $request
					     )));

	  $file = file_get_contents($service, false, $context);
	  $response = xmlrpc_decode($file);




	}
	


	public function onContentPrepare($context, &$item, &$params, $page = 0)
	{
		// Don't run this plugin when the content is being indexed
		if (($context == 'com_finder.indexer') || (!function_exists('xmlrpc_decode')))
		{
			return true;
		}

		// Otherwise see if the content has been marked as updated

	      if ($upd = $this->cache->get('plg_rpcping_content'.$item->id) && ($upd == '1')) {
		    if ($this->sendPing()){
		    // If it was successful, prevent further pings
		      $this->cache->remove('plg_rpcping_content'.$item->id);
		    }
	      }




	}


	/** Runs after pressing Save
	*
	* We don't actually know what the URL is going to be, so we're going to cheat and put something in the cache for onContentPrepare to check for
	*
	*
	*/
	function onAfterSave( & $item, $isNew) {
		   $setfn = $this->setfn;

		  	// Set the cache time to 30 days 
		$this->conf->$setfn('config.cachetime', 604800 );
		$this->cache->setCaching( 1 );
		
		$this->cache->store("1", 'plg_rpcping_content'.$item->id);

		$this->conf->$setfn('config.cachetime', $this->oldcachetime );
		return '';
	}
	
}