<?php return array (
  'dbc966e47b7c45ddd0b44aea67f0846b' => 
  array (
    'criteria' => 
    array (
      'name' => 'babel',
    ),
    'object' => 
    array (
      'name' => 'babel',
      'path' => '{core_path}components/babel/',
      'assets_path' => '',
    ),
  ),
  '7ba479f9c79d60ff17638787c7497698' => 
  array (
    'criteria' => 
    array (
      'key' => 'babel.contextKeys',
    ),
    'object' => 
    array (
      'key' => 'babel.contextKeys',
      'value' => 'web,de',
      'xtype' => 'textfield',
      'namespace' => 'babel',
      'area' => 'common',
      'editedon' => '2013-01-10 15:21:05',
    ),
  ),
  '4b0d89e0f343ad8a71914301ab4179c5' => 
  array (
    'criteria' => 
    array (
      'key' => 'babel.babelTvName',
    ),
    'object' => 
    array (
      'key' => 'babel.babelTvName',
      'value' => 'babelLanguageLinks',
      'xtype' => 'textfield',
      'namespace' => 'babel',
      'area' => 'common',
      'editedon' => '0000-00-00 00:00:00',
    ),
  ),
  '423f0c02c92cfb5d585836fa8d24a161' => 
  array (
    'criteria' => 
    array (
      'key' => 'babel.syncTvs',
    ),
    'object' => 
    array (
      'key' => 'babel.syncTvs',
      'value' => '',
      'xtype' => 'textfield',
      'namespace' => 'babel',
      'area' => 'common',
      'editedon' => '0000-00-00 00:00:00',
    ),
  ),
  '4c746148ffb3a005143cb7a074e7c909' => 
  array (
    'criteria' => 
    array (
      'name' => 'Babel',
    ),
    'object' => 
    array (
      'id' => 3,
      'source' => 0,
      'property_preprocess' => 0,
      'name' => 'Babel',
      'description' => 'Links and synchronizes multilingual resources.',
      'editor_type' => 0,
      'category' => 0,
      'cache_type' => 0,
      'plugincode' => '/**
 * Babel
 *
 * Copyright 2010 by Jakob Class <jakob.class@class-zec.de>
 *
 * This file is part of Babel.
 *
 * Babel is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Babel is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Babel; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package babel
 */
/**
 * Babel Plugin to link and synchronize multilingual resources
 * 
 * Based on ideas of Sylvain Aerni <enzyms@gmail.com>
 *
 * Events:
 * OnDocFormPrerender,OnDocFormSave,OnEmptyTrash,OnContextRemove,OnResourceDuplicate
 *
 * @author Jakob Class <jakob.class@class-zec.de>
 *
 * @package babel
 * 
 */

$babel = $modx->getService(\'babel\',\'Babel\',$modx->getOption(\'babel.core_path\',null,$modx->getOption(\'core_path\').\'components/babel/\').\'model/babel/\',$scriptProperties);

if (!($babel instanceof Babel)) return;

/* be sure babel TV is loaded */
if(!$babel->babelTv) return;

switch ($modx->event->name) {
	case \'OnDocFormPrerender\':
		$output = \'\';
		$errorMessage = \'\';
		$resource =& $modx->event->params[\'resource\'];
		if(!$resource) {
			/* a new resource is being to created
			 * -> skip rendering the babel box */
			break;
		}
		$contextKeys = $babel->getGroupContextKeys($resource->get(\'context_key\'));
		$currentContextKey = $resource->get(\'context_key\');
		$linkedResources = $babel->getLinkedResources($resource->get(\'id\'));
		if(empty($linkedResources)) {
			/* always be sure that the Babel TV is set */
			$babel->initBabelTv($resource);
		}
		
		/* grab manager actions IDs */
		$actions = $modx->request->getAllActionIDs();
		
		if(isset($_POST[\'babel-context-key\'])) {
			/* one of the following babel actions has been performed: link, unlink or translate */
			try {
				$contextKey = $_POST[\'babel-context-key\'];
				/* check if context is valid */
				$context = $modx->getObject(\'modContext\', array(\'key\' => $contextKey));
				if(!$context) {
					$errorParameter = array(\'context\' => $contextKey);
					throw new Exception(\'error.invalid_context_key\');
				}
				
				/* manuallly add or change a translation link */
				if(isset($_POST[\'babel-link\'])) {
					if($linkedResources[$contextKey] == $_POST[\'babel-link-target\']) {
						/* target resource is equal to current resource -> nothing to do */
						throw new Exception();
					}
					$targetResource = $modx->getObject(\'modResource\', intval($_POST[\'babel-link-target\']));
					if(!$targetResource) {
						/* error: resource id is not valid */
						$errorParameter = array(\'resource\' => htmlentities($_POST[\'babel-link-target\']));
						throw new Exception(\'error.invalid_resource_id\');
					}
					if($targetResource->get(\'context_key\') != $contextKey) {
						/* error: resource id of another context has been provided */
						$errorParameter = array(
							\'resource\' => $targetResource->get(\'id\'),
							\'context\' => $contextKey);
						throw new Exception(\'error.resource_from_other_context\');
					}
					$targetLinkedResources = $babel->getLinkedResources($targetResource->get(\'id\'));
					if(count($targetLinkedResources) > 1) {
						/* error: target resource is already linked with other resources */
						$errorParameter = array(\'resource\' => $targetResource->get(\'id\'));
						throw new Exception(\'error.resource_already_linked\');
					}
					/* add or change a translation link */
					if(isset($linkedResources[$contextKey])) {
						/* existing link has been changed:
						 * -> reset Babel TV of old resource */
						$babel->initBabelTvById($linkedResources[$contextKey]);
					}
					
					$linkedResources[$contextKey] = $targetResource->get(\'id\');
					$babel->updateBabelTv($linkedResources, $linkedResources);
					
					/* copy values of synchronized TVs to target resource */
					if(isset($_POST[\'babel-link-copy-tvs\']) && intval($_POST[\'babel-link-copy-tvs\']) == 1) {
						$babel->sychronizeTvs($resource->get(\'id\'));
					}
				}
				
				/* remove an existing translation link */
				if(isset($_POST[\'babel-unlink\'])) {
					if(!isset($linkedResources[$contextKey])) {
						/* error: there is no link for this context */
						$errorParameter = array(\'context\' => $contextKey);
						throw new Exception(\'error.no_link_to_context\');
					}
					if($linkedResources[$contextKey] == $resource->get(\'id\')) {
						/* error: (current) resource can not be unlinked from it\'s translations */
						$errorParameter = array(\'context\' => $contextKey);
						throw new Exception(\'error.unlink_of_selflink_not_possible\');
					}					
					$unlinkedResource = $modx->getObject(\'modResource\', intval($linkedResources[$contextKey]));
					if(!$unlinkedResource) {
						/* error: invalid resource id */
						$errorParameter = array(\'resource\' => htmlentities($linkedResources[$contextKey]));
						throw new Exception(\'error.invalid_resource_id\');
					}
					if($unlinkedResource->get(\'context_key\') != $contextKey) {
						/* error: resource is of a another context */
						$errorParameter = array(
							\'resource\' => $targetResource->get(\'id\'),
							\'context\' => $contextKey);
						throw new Exception(\'error.resource_from_other_context\');
					}
					/* unlink resource and reset its Babel TV */
					$babel->initBabelTv($unlinkedResource);
					unset($linkedResources[$contextKey]);
					$babel->updateBabelTv($linkedResources, $linkedResources);
						
				}
				
				/* create an new resource an add a translation link */
				if(isset($_POST[\'babel-translate\'])) {
					if($currentContextKey == $contextKey) {
						/* error: translation should be created in the same context */
						throw new Exception(\'error.translation_in_same_context\');
					}
					if(isset($linkedResources[$contextKey])) {
						/* error: there does already exist a translation */
						$errorParameter = array(\'context\' => $contextKey);
						throw new Exception(\'error.translation_already_exists\');
					}
										
					$newResource = $babel->duplicateResource($resource, $contextKey);
					if($newResource) {										
						$linkedResources[$contextKey] = $newResource->get(\'id\');
						$babel->updateBabelTv($linkedResources, $linkedResources);
					} else {
						/* error: translation could not be created */
						$errorParameter = array(\'context\' => $contextKey);
						throw new Exception(\'error.could_not_create_translation\');
					}
					/* redirect to new resource */
					$url = $modx->getOption(\'manager_url\',null,MODX_MANAGER_URL).\'?a=\'.$actions[\'resource/update\'].\'&id=\'.$newResource->get(\'id\');
					$modx->sendRedirect(rtrim($url,\'/\'),\'\',\'\',\'full\');
				}
			} catch (Exception $exception) {
				$errorKey = $exception->getMessage();
				if($errorKey) {
					if(!is_array($errorParameter)) {
						$errorParameter = array();
					}
					$errorMessage = \'<div id="babel-error">\'.$modx->lexicon($errorKey,$errorParameter).\'</div>\';
				}
			}

		}
		
		/* create babel-box with links to translations */
		$linkedResources = $babel->getLinkedResources($resource->get(\'id\'));
		$outputLanguageItems = \'\';
		foreach($contextKeys as $contextKey) {
			/* for each (valid/existing) context of the context group a button will be displayed */
			$context = $modx->getObject(\'modContext\', array(\'key\' => $contextKey));
			if(!$context) {
				$modx->log(modX::LOG_LEVEL_ERROR, \'Could not load context: \'.$contextKey);
				continue;
			}
			$context->prepare();
			$cultureKey = $context->getOption(\'cultureKey\',$modx->getOption(\'cultureKey\'));
			/* url to which the form will post it\'s data */
			$formUrl = \'?a=\'.$actions[\'resource/update\'].\'&amp;id=\'.$resource->get(\'id\');
			if(isset($linkedResources[$contextKey])) {
				/* link to this context has been set */
				if($linkedResources[$contextKey] == $resource->get(\'id\')) {
					/* don\'t show language layer for current resource */
					$showLayer = \'\';
				} else {
					$showLayer = \'yes\';
				}
				$showTranslateButton = \'\';
				$showUnlinkButton = \'yes\';
				$showSecondRow = \'\';
				$resourceId = $linkedResources[$contextKey];
				$resourceUrl = \'?a=\'.$actions[\'resource/update\'].\'&amp;id=\'.$resourceId;
				if($resourceId == $resource->get(\'id\')) {
					$className = \'selected\';
				} else {
					$className = \'\';
				}
				
			} else {
				/* link to this context has not been set yet:
				 * -> show button to create translation */
				$showLayer = \'yes\';
				$showTranslateButton = \'yes\';
				$showUnlinkButton = \'\';
				$showSecondRow = \'yes\';
				$resourceId = \'\';
				$resourceUrl = \'#\';
				$className = \'notset\';
			}
			$placeholders = array(
				\'formUrl\' => $formUrl,
				\'contextKey\' => $contextKey,
				\'cultureKey\' => $cultureKey,
				\'resourceId\' => $resourceId,
				\'resourceUrl\' => $resourceUrl,
				\'className\' => $className,
				\'showLayer\' => $showLayer,
				\'showTranslateButton\' => $showTranslateButton,
				\'showUnlinkButton\' => $showUnlinkButton,
				\'showSecondRow\' => $showSecondRow,
			);
			$outputLanguageItems .= $babel->getChunk(\'mgr/babelBoxItem\', $placeholders);
		}
		
		$output .= \'<div id="babel-box">\'.$errorMessage.$outputLanguageItems.\'</div>\';
		$modx->event->output($output);
		
		/* include CSS */
		$modx->regClientCSS($babel->config[\'cssUrl\'].\'babel.css?v=6\');
		$modx->regClientStartupScript($babel->config[\'jsUrl\'].\'babel.js?v=3\');
		break;
	
	case \'OnDocFormSave\':
		$resource =& $modx->event->params[\'resource\'];
		if(!$resource) {
			$modx->log(modX::LOG_LEVEL_ERROR, \'No resource provided for OnDocFormSave event\');
			break;
		}
		if($modx->event->params[\'mode\'] == modSystemEvent::MODE_NEW) {
			/* no TV synchronization for new resources, just init Babel TV */
			$babel->initBabelTv($resource);
			break;
		}
		$babel->sychronizeTvs($resource->get(\'id\'));
		break;
		
	case \'OnEmptyTrash\':
		/* remove translation links to non-existing resources */
		$deletedResourceIds =& $modx->event->params[\'ids\'];
		if(is_array($deletedResourceIds)) {
			foreach ($deletedResourceIds as $deletedResourceId) {
				$babel->removeLanguageLinksToResource($deletedResourceId);
			}
		}		
		break;
		
	case \'OnContextRemove\':
		/* remove translation links to non-existing contexts */
		$context =& $modx->event->params[\'context\'];
		if($context) {
			$babel->removeLanguageLinksToContext($context->get(\'key\'));
		}
		break;
	
	case \'OnResourceDuplicate\':
		/* init Babel TV of duplicated resources */
		$resource =& $modx->event->params[\'newResource\'];
		$babel->initBabelTv($resource);
		break;
}
return;',
      'locked' => 0,
      'properties' => NULL,
      'disabled' => 0,
      'moduleguid' => '',
      'static' => 0,
      'static_file' => '',
      'content' => '/**
 * Babel
 *
 * Copyright 2010 by Jakob Class <jakob.class@class-zec.de>
 *
 * This file is part of Babel.
 *
 * Babel is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Babel is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Babel; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package babel
 */
/**
 * Babel Plugin to link and synchronize multilingual resources
 * 
 * Based on ideas of Sylvain Aerni <enzyms@gmail.com>
 *
 * Events:
 * OnDocFormPrerender,OnDocFormSave,OnEmptyTrash,OnContextRemove,OnResourceDuplicate
 *
 * @author Jakob Class <jakob.class@class-zec.de>
 *
 * @package babel
 * 
 */

$babel = $modx->getService(\'babel\',\'Babel\',$modx->getOption(\'babel.core_path\',null,$modx->getOption(\'core_path\').\'components/babel/\').\'model/babel/\',$scriptProperties);

if (!($babel instanceof Babel)) return;

/* be sure babel TV is loaded */
if(!$babel->babelTv) return;

switch ($modx->event->name) {
	case \'OnDocFormPrerender\':
		$output = \'\';
		$errorMessage = \'\';
		$resource =& $modx->event->params[\'resource\'];
		if(!$resource) {
			/* a new resource is being to created
			 * -> skip rendering the babel box */
			break;
		}
		$contextKeys = $babel->getGroupContextKeys($resource->get(\'context_key\'));
		$currentContextKey = $resource->get(\'context_key\');
		$linkedResources = $babel->getLinkedResources($resource->get(\'id\'));
		if(empty($linkedResources)) {
			/* always be sure that the Babel TV is set */
			$babel->initBabelTv($resource);
		}
		
		/* grab manager actions IDs */
		$actions = $modx->request->getAllActionIDs();
		
		if(isset($_POST[\'babel-context-key\'])) {
			/* one of the following babel actions has been performed: link, unlink or translate */
			try {
				$contextKey = $_POST[\'babel-context-key\'];
				/* check if context is valid */
				$context = $modx->getObject(\'modContext\', array(\'key\' => $contextKey));
				if(!$context) {
					$errorParameter = array(\'context\' => $contextKey);
					throw new Exception(\'error.invalid_context_key\');
				}
				
				/* manuallly add or change a translation link */
				if(isset($_POST[\'babel-link\'])) {
					if($linkedResources[$contextKey] == $_POST[\'babel-link-target\']) {
						/* target resource is equal to current resource -> nothing to do */
						throw new Exception();
					}
					$targetResource = $modx->getObject(\'modResource\', intval($_POST[\'babel-link-target\']));
					if(!$targetResource) {
						/* error: resource id is not valid */
						$errorParameter = array(\'resource\' => htmlentities($_POST[\'babel-link-target\']));
						throw new Exception(\'error.invalid_resource_id\');
					}
					if($targetResource->get(\'context_key\') != $contextKey) {
						/* error: resource id of another context has been provided */
						$errorParameter = array(
							\'resource\' => $targetResource->get(\'id\'),
							\'context\' => $contextKey);
						throw new Exception(\'error.resource_from_other_context\');
					}
					$targetLinkedResources = $babel->getLinkedResources($targetResource->get(\'id\'));
					if(count($targetLinkedResources) > 1) {
						/* error: target resource is already linked with other resources */
						$errorParameter = array(\'resource\' => $targetResource->get(\'id\'));
						throw new Exception(\'error.resource_already_linked\');
					}
					/* add or change a translation link */
					if(isset($linkedResources[$contextKey])) {
						/* existing link has been changed:
						 * -> reset Babel TV of old resource */
						$babel->initBabelTvById($linkedResources[$contextKey]);
					}
					
					$linkedResources[$contextKey] = $targetResource->get(\'id\');
					$babel->updateBabelTv($linkedResources, $linkedResources);
					
					/* copy values of synchronized TVs to target resource */
					if(isset($_POST[\'babel-link-copy-tvs\']) && intval($_POST[\'babel-link-copy-tvs\']) == 1) {
						$babel->sychronizeTvs($resource->get(\'id\'));
					}
				}
				
				/* remove an existing translation link */
				if(isset($_POST[\'babel-unlink\'])) {
					if(!isset($linkedResources[$contextKey])) {
						/* error: there is no link for this context */
						$errorParameter = array(\'context\' => $contextKey);
						throw new Exception(\'error.no_link_to_context\');
					}
					if($linkedResources[$contextKey] == $resource->get(\'id\')) {
						/* error: (current) resource can not be unlinked from it\'s translations */
						$errorParameter = array(\'context\' => $contextKey);
						throw new Exception(\'error.unlink_of_selflink_not_possible\');
					}					
					$unlinkedResource = $modx->getObject(\'modResource\', intval($linkedResources[$contextKey]));
					if(!$unlinkedResource) {
						/* error: invalid resource id */
						$errorParameter = array(\'resource\' => htmlentities($linkedResources[$contextKey]));
						throw new Exception(\'error.invalid_resource_id\');
					}
					if($unlinkedResource->get(\'context_key\') != $contextKey) {
						/* error: resource is of a another context */
						$errorParameter = array(
							\'resource\' => $targetResource->get(\'id\'),
							\'context\' => $contextKey);
						throw new Exception(\'error.resource_from_other_context\');
					}
					/* unlink resource and reset its Babel TV */
					$babel->initBabelTv($unlinkedResource);
					unset($linkedResources[$contextKey]);
					$babel->updateBabelTv($linkedResources, $linkedResources);
						
				}
				
				/* create an new resource an add a translation link */
				if(isset($_POST[\'babel-translate\'])) {
					if($currentContextKey == $contextKey) {
						/* error: translation should be created in the same context */
						throw new Exception(\'error.translation_in_same_context\');
					}
					if(isset($linkedResources[$contextKey])) {
						/* error: there does already exist a translation */
						$errorParameter = array(\'context\' => $contextKey);
						throw new Exception(\'error.translation_already_exists\');
					}
										
					$newResource = $babel->duplicateResource($resource, $contextKey);
					if($newResource) {										
						$linkedResources[$contextKey] = $newResource->get(\'id\');
						$babel->updateBabelTv($linkedResources, $linkedResources);
					} else {
						/* error: translation could not be created */
						$errorParameter = array(\'context\' => $contextKey);
						throw new Exception(\'error.could_not_create_translation\');
					}
					/* redirect to new resource */
					$url = $modx->getOption(\'manager_url\',null,MODX_MANAGER_URL).\'?a=\'.$actions[\'resource/update\'].\'&id=\'.$newResource->get(\'id\');
					$modx->sendRedirect(rtrim($url,\'/\'),\'\',\'\',\'full\');
				}
			} catch (Exception $exception) {
				$errorKey = $exception->getMessage();
				if($errorKey) {
					if(!is_array($errorParameter)) {
						$errorParameter = array();
					}
					$errorMessage = \'<div id="babel-error">\'.$modx->lexicon($errorKey,$errorParameter).\'</div>\';
				}
			}

		}
		
		/* create babel-box with links to translations */
		$linkedResources = $babel->getLinkedResources($resource->get(\'id\'));
		$outputLanguageItems = \'\';
		foreach($contextKeys as $contextKey) {
			/* for each (valid/existing) context of the context group a button will be displayed */
			$context = $modx->getObject(\'modContext\', array(\'key\' => $contextKey));
			if(!$context) {
				$modx->log(modX::LOG_LEVEL_ERROR, \'Could not load context: \'.$contextKey);
				continue;
			}
			$context->prepare();
			$cultureKey = $context->getOption(\'cultureKey\',$modx->getOption(\'cultureKey\'));
			/* url to which the form will post it\'s data */
			$formUrl = \'?a=\'.$actions[\'resource/update\'].\'&amp;id=\'.$resource->get(\'id\');
			if(isset($linkedResources[$contextKey])) {
				/* link to this context has been set */
				if($linkedResources[$contextKey] == $resource->get(\'id\')) {
					/* don\'t show language layer for current resource */
					$showLayer = \'\';
				} else {
					$showLayer = \'yes\';
				}
				$showTranslateButton = \'\';
				$showUnlinkButton = \'yes\';
				$showSecondRow = \'\';
				$resourceId = $linkedResources[$contextKey];
				$resourceUrl = \'?a=\'.$actions[\'resource/update\'].\'&amp;id=\'.$resourceId;
				if($resourceId == $resource->get(\'id\')) {
					$className = \'selected\';
				} else {
					$className = \'\';
				}
				
			} else {
				/* link to this context has not been set yet:
				 * -> show button to create translation */
				$showLayer = \'yes\';
				$showTranslateButton = \'yes\';
				$showUnlinkButton = \'\';
				$showSecondRow = \'yes\';
				$resourceId = \'\';
				$resourceUrl = \'#\';
				$className = \'notset\';
			}
			$placeholders = array(
				\'formUrl\' => $formUrl,
				\'contextKey\' => $contextKey,
				\'cultureKey\' => $cultureKey,
				\'resourceId\' => $resourceId,
				\'resourceUrl\' => $resourceUrl,
				\'className\' => $className,
				\'showLayer\' => $showLayer,
				\'showTranslateButton\' => $showTranslateButton,
				\'showUnlinkButton\' => $showUnlinkButton,
				\'showSecondRow\' => $showSecondRow,
			);
			$outputLanguageItems .= $babel->getChunk(\'mgr/babelBoxItem\', $placeholders);
		}
		
		$output .= \'<div id="babel-box">\'.$errorMessage.$outputLanguageItems.\'</div>\';
		$modx->event->output($output);
		
		/* include CSS */
		$modx->regClientCSS($babel->config[\'cssUrl\'].\'babel.css?v=6\');
		$modx->regClientStartupScript($babel->config[\'jsUrl\'].\'babel.js?v=3\');
		break;
	
	case \'OnDocFormSave\':
		$resource =& $modx->event->params[\'resource\'];
		if(!$resource) {
			$modx->log(modX::LOG_LEVEL_ERROR, \'No resource provided for OnDocFormSave event\');
			break;
		}
		if($modx->event->params[\'mode\'] == modSystemEvent::MODE_NEW) {
			/* no TV synchronization for new resources, just init Babel TV */
			$babel->initBabelTv($resource);
			break;
		}
		$babel->sychronizeTvs($resource->get(\'id\'));
		break;
		
	case \'OnEmptyTrash\':
		/* remove translation links to non-existing resources */
		$deletedResourceIds =& $modx->event->params[\'ids\'];
		if(is_array($deletedResourceIds)) {
			foreach ($deletedResourceIds as $deletedResourceId) {
				$babel->removeLanguageLinksToResource($deletedResourceId);
			}
		}		
		break;
		
	case \'OnContextRemove\':
		/* remove translation links to non-existing contexts */
		$context =& $modx->event->params[\'context\'];
		if($context) {
			$babel->removeLanguageLinksToContext($context->get(\'key\'));
		}
		break;
	
	case \'OnResourceDuplicate\':
		/* init Babel TV of duplicated resources */
		$resource =& $modx->event->params[\'newResource\'];
		$babel->initBabelTv($resource);
		break;
}
return;',
    ),
  ),
  '1a8e23961ec25512ec607dfba8b6cdb2' => 
  array (
    'criteria' => 
    array (
      'pluginid' => 3,
      'event' => 'OnDocFormPrerender',
    ),
    'object' => 
    array (
      'pluginid' => 3,
      'event' => 'OnDocFormPrerender',
      'priority' => 0,
      'propertyset' => 0,
    ),
  ),
  'af7f3809848c7d079949d494a287f447' => 
  array (
    'criteria' => 
    array (
      'pluginid' => 3,
      'event' => 'OnDocFormSave',
    ),
    'object' => 
    array (
      'pluginid' => 3,
      'event' => 'OnDocFormSave',
      'priority' => 0,
      'propertyset' => 0,
    ),
  ),
  '553b105fcf247105343105c4d84744ce' => 
  array (
    'criteria' => 
    array (
      'pluginid' => 3,
      'event' => 'OnEmptyTrash',
    ),
    'object' => 
    array (
      'pluginid' => 3,
      'event' => 'OnEmptyTrash',
      'priority' => 0,
      'propertyset' => 0,
    ),
  ),
  '899071b1e82231f718675c5276d231f7' => 
  array (
    'criteria' => 
    array (
      'pluginid' => 3,
      'event' => 'OnContextRemove',
    ),
    'object' => 
    array (
      'pluginid' => 3,
      'event' => 'OnContextRemove',
      'priority' => 0,
      'propertyset' => 0,
    ),
  ),
  '0b8c1d7d9707c4d18e934011f35d7b09' => 
  array (
    'criteria' => 
    array (
      'pluginid' => 3,
      'event' => 'OnResourceDuplicate',
    ),
    'object' => 
    array (
      'pluginid' => 3,
      'event' => 'OnResourceDuplicate',
      'priority' => 0,
      'propertyset' => 0,
    ),
  ),
  '84b28b4467f24035cb42f3eb0c607bbd' => 
  array (
    'criteria' => 
    array (
      'category' => 'Babel',
    ),
    'object' => 
    array (
      'id' => 3,
      'parent' => 0,
      'category' => 'Babel',
    ),
    'files' => 
    array (
      0 => '/var/www/bc_sub/core/components',
    ),
  ),
  'ece752f93b29f154fa9315194fe6a295' => 
  array (
    'criteria' => 
    array (
      'name' => 'BabelLinks',
    ),
    'object' => 
    array (
      'id' => 9,
      'source' => 0,
      'property_preprocess' => 0,
      'name' => 'BabelLinks',
      'description' => 'Displays links to translated resources.',
      'editor_type' => 0,
      'category' => 3,
      'cache_type' => 0,
      'snippet' => '/**
 * Babel
 *
 * Copyright 2010 by Jakob Class <jakob.class@class-zec.de>
 *
 * This file is part of Babel.
 *
 * Babel is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Babel is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Babel; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package babel
 */
/**
 * BabelLinks snippet to display links to translated resources
 * 
 * Based on ideas of Sylvain Aerni <enzyms@gmail.com>
 *
 * @author Jakob Class <jakob.class@class-zec.de>
 *
 * @package babel
 * 
 * @param resourceId		optional: id of resource of which links to translations should be displayed. Default: current resource
 * @param tpl				optional: Chunk to display a language link. Default: babelLink
 * @param activeCls			optional: CSS class name for the current active language. Default: active
 * @param showUnpublished	optional: flag whether to show unpublished translations. Default: 0
 * @param showCurrent		optional: flag whether to show a link to a translation of the current language. Default: 1
 */
$babel = $modx->getService(\'babel\',\'Babel\',$modx->getOption(\'babel.core_path\',null,$modx->getOption(\'core_path\').\'components/babel/\').\'model/babel/\',$scriptProperties);

if (!($babel instanceof Babel)) return;

/* be sure babel TV is loaded */
if(!$babel->babelTv) return;

/* get snippet properties */
$resourceId = $modx->resource->get(\'id\');
if(!empty($scriptProperties[\'resourceId\'])) {
	$resourceId = intval($modx->getOption(\'resourceId\',$scriptProperties,$resourceId));
}
$tpl = $modx->getOption(\'tpl\',$scriptProperties,\'babelLink\');
$activeCls = $modx->getOption(\'activeCls\',$scriptProperties,\'active\');
$showUnpublished = $modx->getOption(\'showUnpublished\',$scriptProperties,0);
$showCurrent = $modx->getOption(\'showCurrent\',$scriptProperties,1);

if($resourceId == $modx->resource->get(\'id\')) {
	$contextKeys = $babel->getGroupContextKeys($modx->resource->get(\'context_key\'));
} else {
	$resource = $modx->getObject(\'modResource\', $resourceId);
	if(!$resource) {
		return;
	}
	$contextKeys = $babel->getGroupContextKeys($resource->get(\'context_key\'));
}

$linkedResources = $babel->getLinkedResources($resourceId);

$output = \'\';
foreach($contextKeys as $contextKey) {
	if(!$showCurrent && $contextKey == $modx->resource->get(\'context_key\')) {
		continue;
	}
	$context = $modx->getObject(\'modContext\', array(\'key\' => $contextKey));
	if(!$context) {
		$modx->log(modX::LOG_LEVEL_ERROR, \'Could not load context: \'.$contextKey);
		continue;
	}
	$context->prepare();
	$cultureKey = $context->getOption(\'cultureKey\',$modx->getOption(\'cultureKey\'));
	$translationAvailable = false;
	if(isset($linkedResources[$contextKey])) {
		$resource = $modx->getObject(\'modResource\',$linkedResources[$contextKey]);
		if($resource && ($showUnpublished || $resource->get(\'published\') == 1)) {
			$translationAvailable = true;
		}
	}
	if($translationAvailable) {
		$url = $context->makeUrl($linkedResources[$contextKey],\'\',\'full\');
	} else {
		$url = $context->getOption(\'site_url\', $modx->getOption(\'site_url\'));
	}
	$active = ($modx->resource->get(\'context_key\') == $contextKey) ? $activeCls : \'\';
	$placeholders = array(
		\'cultureKey\' => $cultureKey,
		\'url\' => $url,
		\'active\' => $active,
		\'id\' => $translationAvailable? $linkedResources[$contextKey] : \'\');
	$output .= $babel->getChunk($tpl,$placeholders);
}
  
return $output;',
      'locked' => 0,
      'properties' => 'a:5:{s:10:"resourceId";a:6:{s:4:"name";s:10:"resourceId";s:4:"desc";s:21:"babellinks.resourceId";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:16:"babel:properties";}s:3:"tpl";a:6:{s:4:"name";s:3:"tpl";s:4:"desc";s:14:"babellinks.tpl";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:9:"babelLink";s:7:"lexicon";s:16:"babel:properties";}s:9:"activeCls";a:6:{s:4:"name";s:9:"activeCls";s:4:"desc";s:20:"babellinks.activeCls";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:6:"active";s:7:"lexicon";s:16:"babel:properties";}s:15:"showUnpublished";a:6:{s:4:"name";s:15:"showUnpublished";s:4:"desc";s:26:"babellinks.showUnpublished";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:1:"0";s:7:"lexicon";s:16:"babel:properties";}s:11:"showCurrent";a:6:{s:4:"name";s:11:"showCurrent";s:4:"desc";s:22:"babellinks.showCurrent";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:1:"1";s:7:"lexicon";s:16:"babel:properties";}}',
      'moduleguid' => '',
      'static' => 0,
      'static_file' => '',
      'content' => '/**
 * Babel
 *
 * Copyright 2010 by Jakob Class <jakob.class@class-zec.de>
 *
 * This file is part of Babel.
 *
 * Babel is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Babel is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Babel; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package babel
 */
/**
 * BabelLinks snippet to display links to translated resources
 * 
 * Based on ideas of Sylvain Aerni <enzyms@gmail.com>
 *
 * @author Jakob Class <jakob.class@class-zec.de>
 *
 * @package babel
 * 
 * @param resourceId		optional: id of resource of which links to translations should be displayed. Default: current resource
 * @param tpl				optional: Chunk to display a language link. Default: babelLink
 * @param activeCls			optional: CSS class name for the current active language. Default: active
 * @param showUnpublished	optional: flag whether to show unpublished translations. Default: 0
 * @param showCurrent		optional: flag whether to show a link to a translation of the current language. Default: 1
 */
$babel = $modx->getService(\'babel\',\'Babel\',$modx->getOption(\'babel.core_path\',null,$modx->getOption(\'core_path\').\'components/babel/\').\'model/babel/\',$scriptProperties);

if (!($babel instanceof Babel)) return;

/* be sure babel TV is loaded */
if(!$babel->babelTv) return;

/* get snippet properties */
$resourceId = $modx->resource->get(\'id\');
if(!empty($scriptProperties[\'resourceId\'])) {
	$resourceId = intval($modx->getOption(\'resourceId\',$scriptProperties,$resourceId));
}
$tpl = $modx->getOption(\'tpl\',$scriptProperties,\'babelLink\');
$activeCls = $modx->getOption(\'activeCls\',$scriptProperties,\'active\');
$showUnpublished = $modx->getOption(\'showUnpublished\',$scriptProperties,0);
$showCurrent = $modx->getOption(\'showCurrent\',$scriptProperties,1);

if($resourceId == $modx->resource->get(\'id\')) {
	$contextKeys = $babel->getGroupContextKeys($modx->resource->get(\'context_key\'));
} else {
	$resource = $modx->getObject(\'modResource\', $resourceId);
	if(!$resource) {
		return;
	}
	$contextKeys = $babel->getGroupContextKeys($resource->get(\'context_key\'));
}

$linkedResources = $babel->getLinkedResources($resourceId);

$output = \'\';
foreach($contextKeys as $contextKey) {
	if(!$showCurrent && $contextKey == $modx->resource->get(\'context_key\')) {
		continue;
	}
	$context = $modx->getObject(\'modContext\', array(\'key\' => $contextKey));
	if(!$context) {
		$modx->log(modX::LOG_LEVEL_ERROR, \'Could not load context: \'.$contextKey);
		continue;
	}
	$context->prepare();
	$cultureKey = $context->getOption(\'cultureKey\',$modx->getOption(\'cultureKey\'));
	$translationAvailable = false;
	if(isset($linkedResources[$contextKey])) {
		$resource = $modx->getObject(\'modResource\',$linkedResources[$contextKey]);
		if($resource && ($showUnpublished || $resource->get(\'published\') == 1)) {
			$translationAvailable = true;
		}
	}
	if($translationAvailable) {
		$url = $context->makeUrl($linkedResources[$contextKey],\'\',\'full\');
	} else {
		$url = $context->getOption(\'site_url\', $modx->getOption(\'site_url\'));
	}
	$active = ($modx->resource->get(\'context_key\') == $contextKey) ? $activeCls : \'\';
	$placeholders = array(
		\'cultureKey\' => $cultureKey,
		\'url\' => $url,
		\'active\' => $active,
		\'id\' => $translationAvailable? $linkedResources[$contextKey] : \'\');
	$output .= $babel->getChunk($tpl,$placeholders);
}
  
return $output;',
    ),
  ),
  'a920123f132985c511e8dcab8f6db2bc' => 
  array (
    'criteria' => 
    array (
      'name' => 'BabelTranslation',
    ),
    'object' => 
    array (
      'id' => 10,
      'source' => 0,
      'property_preprocess' => 0,
      'name' => 'BabelTranslation',
      'description' => 'Returns the id of a translated resource in a given context.',
      'editor_type' => 0,
      'category' => 3,
      'cache_type' => 0,
      'snippet' => '/**
 * Babel
 *
 * Copyright 2010 by Jakob Class <jakob.class@class-zec.de>
 *
 * This file is part of Babel.
 *
 * Babel is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Babel is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Babel; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package babel
 */
/**
 * BabelTranslation snippet to get the id of a translated resource in a given context.
 *
 * @author Jakob Class <jakob.class@class-zec.de>
 *
 * @package babel
 * 
 * @param resourceId		optional: id of resource of which a translated resource should be determined. Default: current resource
 * @param contextKey		Key of context in which translated resource should be determined.
 * @param showUnpublished	optional: flag whether to show unpublished translations. Default: 0
 */
$babel = $modx->getService(\'babel\',\'Babel\',$modx->getOption(\'babel.core_path\',null,$modx->getOption(\'core_path\').\'components/babel/\').\'model/babel/\',$scriptProperties);

if (!($babel instanceof Babel)) return;

/* be sure babel TV is loaded */
if(!$babel->babelTv) return;

/* get snippet properties */
$resourceId = $modx->resource->get(\'id\');
if(!empty($scriptProperties[\'resourceId\'])) {
	$resourceId = intval($modx->getOption(\'resourceId\',$scriptProperties,$resourceId));
}
$contextKey = $modx->getOption(\'contextKey\',$scriptProperties,\'\');
$showUnpublished = $modx->getOption(\'showUnpublished\',$scriptProperties,0);

/* determine id of tranlated resource */
$linkedResources = $babel->getLinkedResources($resourceId);
$output = null;
if(isset($linkedResources[$contextKey])) {
	$resource = $modx->getObject(\'modResource\',$linkedResources[$contextKey]);
	if($resource && ($showUnpublished || $resource->get(\'published\') == 1)) {
		$output = $resource->get(\'id\');
	}
}
return $output;',
      'locked' => 0,
      'properties' => 'a:3:{s:10:"resourceId";a:6:{s:4:"name";s:10:"resourceId";s:4:"desc";s:27:"babeltranslation.resourceId";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:16:"babel:properties";}s:10:"contextKey";a:6:{s:4:"name";s:10:"contextKey";s:4:"desc";s:27:"babeltranslation.contextKey";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:16:"babel:properties";}s:15:"showUnpublished";a:6:{s:4:"name";s:15:"showUnpublished";s:4:"desc";s:32:"babeltranslation.showUnpublished";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:1:"0";s:7:"lexicon";s:16:"babel:properties";}}',
      'moduleguid' => '',
      'static' => 0,
      'static_file' => '',
      'content' => '/**
 * Babel
 *
 * Copyright 2010 by Jakob Class <jakob.class@class-zec.de>
 *
 * This file is part of Babel.
 *
 * Babel is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Babel is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Babel; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package babel
 */
/**
 * BabelTranslation snippet to get the id of a translated resource in a given context.
 *
 * @author Jakob Class <jakob.class@class-zec.de>
 *
 * @package babel
 * 
 * @param resourceId		optional: id of resource of which a translated resource should be determined. Default: current resource
 * @param contextKey		Key of context in which translated resource should be determined.
 * @param showUnpublished	optional: flag whether to show unpublished translations. Default: 0
 */
$babel = $modx->getService(\'babel\',\'Babel\',$modx->getOption(\'babel.core_path\',null,$modx->getOption(\'core_path\').\'components/babel/\').\'model/babel/\',$scriptProperties);

if (!($babel instanceof Babel)) return;

/* be sure babel TV is loaded */
if(!$babel->babelTv) return;

/* get snippet properties */
$resourceId = $modx->resource->get(\'id\');
if(!empty($scriptProperties[\'resourceId\'])) {
	$resourceId = intval($modx->getOption(\'resourceId\',$scriptProperties,$resourceId));
}
$contextKey = $modx->getOption(\'contextKey\',$scriptProperties,\'\');
$showUnpublished = $modx->getOption(\'showUnpublished\',$scriptProperties,0);

/* determine id of tranlated resource */
$linkedResources = $babel->getLinkedResources($resourceId);
$output = null;
if(isset($linkedResources[$contextKey])) {
	$resource = $modx->getObject(\'modResource\',$linkedResources[$contextKey]);
	if($resource && ($showUnpublished || $resource->get(\'published\') == 1)) {
		$output = $resource->get(\'id\');
	}
}
return $output;',
    ),
  ),
);