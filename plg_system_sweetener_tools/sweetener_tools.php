<?php
/**
 * @package    Joomla - Sweetener tools
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - Webmasterskaya
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

class PlgSystemSweetener_tools extends CMSPlugin
{
	/**
	 * Exclude Modules function enable.
	 *
	 * @var  boolean
	 *
	 * @since  1.0.0
	 */
	protected $unset_modules = false;

	/**
	 * Affects constructor behavior.
	 *
	 * @var  boolean
	 *
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array    $config   An optional associative array of configuration settings.
	 *
	 * @since  1.0.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		// Set functions status
		$this->unset_modules = ($this->params->get('unset_modules')) ? true : false;
	}

	/**
	 * @param   \Joomla\CMS\Form\Form  $form
	 * @param   array                  $data
	 *
	 *
	 * @since  1.0.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		// Change modules form
		if ($this->unset_modules
			&& in_array($form->getName(),
				array('com_modules.module', 'com_advancedmodules.module', 'com_config.modules')))
		{
			Form::addFormPath(__DIR__ . '/forms');
			$form->loadFile('unset');
			$jyproextra        = PluginHelper::getPlugin('system', 'jyproextra');
			$jyproextra_params = new Registry($jyproextra->params);
			if ($jyproextra_params->get('unset_modules'))
			{
				$form->removeField('unset_components', 'params');
				$form->removeField('unset_empty', 'params');
				$override = new SimpleXMLElement('<field name="unset_components" type="hidden"/>');
				$form->setField($override, 'params', true, 'unset');
				if (!empty($data->params['unset_components']))
				{
					unset($data->params['unset_components']);
				}
			}
			else
			{
				$note = new SimpleXMLElement('<field type="note" label="PLG_SYSTEM_SWEETENER_TOOLS_UNSET_MODULES" description="PLG_SYSTEM_SWEETENER_TOOLS_UNSET_MODULES_DESC" class="alert alert-info"/>');
				$form->setField($note, 'params', true, 'unset');
			}
			// Add params
			$form->loadFile('module');
		}
	}

	/**
	 * Method to unset modules based on module params.
	 *
	 * @param   array  $modules  The modules array.
	 *
	 * @throws Exception
	 * @since  1.0.0
	 */
	public function onAfterCleanModuleList(&$modules)
	{
		if ($this->unset_modules && !empty($modules) && Factory::getApplication()->isClient('site')
			&& Factory::getApplication()->getTemplate() === 'yootheme')
		{
			$resetKeys       = false;
			$component       = Factory::getApplication()->input->get('option');
			$view            = Factory::getApplication()->input->get('view');
			$layout          = Factory::getApplication()->input->get('layout');
			$controller      = Factory::getApplication()->input->get('controller',
				Factory::getApplication()->input->get('ctrl'));
			$unsetView       = ($view) ? $component . '.' . $view : false;
			$unsetLayout     = ($unsetView && $layout) ? $unsetView . ':' . $layout : false;
			$unsetController = (!$view && !$layout && $controller) ? $component . '.' . $controller : false;

			foreach ($modules as $key => $module)
			{
				$params          = new Registry($module->params);
				$unsetMode       = $params->get('exclude_mode', 0);
				$unsetComponents = $params->get('exclude_components', array());

				// Unset in components views
				if ($unsetComponents && (($unsetView && in_array($unsetView, $unsetComponents))
						|| ($unsetLayout && in_array($unsetLayout, $unsetComponents))
						|| ($unsetController && in_array($unsetController, $unsetComponents))))
				{
					$resetKeys = true;
					if (!$unsetMode)
					{
						unset($modules[$key]);
					}
					else
					{
						continue;
					}
				} // Unset empty content modules
				elseif ($params->get('unset_empty') && empty(trim(ModuleHelper::renderModule($module))))
				{
					$resetKeys = true;
					unset($modules[$key]);
				}

				if ($unsetComponents && $unsetMode)
				{
					$resetKeys = true;
					unset($modules[$key]);
				}
			}

			// Reset modules array keys
			if ($resetKeys)
			{
				$modules = array_values($modules);
			}
		}
	}

	/**
	 * Method to unset module based on module params.
	 *
	 * @param   object  $module  The module object.
	 *
	 * @throws Exception
	 * @since  1.0.0
	 */
	public function onRenderModule(&$module)
	{
		if ($this->unset_modules && !empty($module->params) && Factory::getApplication()->isClient('site'))
		{
			$params          = new Registry($module->params);
			$component       = Factory::getApplication()->input->get('option');
			$view            = Factory::getApplication()->input->get('view');
			$layout          = Factory::getApplication()->input->get('layout');
			$controller      = Factory::getApplication()->input->get('controller',
				Factory::getApplication()->input->get('ctrl'));
			$unsetView       = ($view) ? $component . '.' . $view : false;
			$unsetLayout     = ($unsetView && $layout) ? $unsetView . ':' . $layout : false;
			$unsetController = (!$view && !$layout && $controller) ? $component . '.' . $controller : false;
			$unsetMode       = $params->get('exclude_mode', 0);
			$unsetComponents = $params->get('exclude_components', array());

			// Unset in YOOtheme Pro customizer
			if ($unsetComponents && (($unsetView && in_array($unsetView, $unsetComponents))
					|| ($unsetLayout && in_array($unsetLayout, $unsetComponents))
					|| ($unsetController && in_array($unsetController, $unsetComponents))))
			{
				if (!$unsetMode)
				{
					$module = null;
				}
			} // Unset empty content modules
			elseif ($params->get('unset_empty') && empty(trim($module->content)))
			{
				$module = null;
			}
		}
	}
}