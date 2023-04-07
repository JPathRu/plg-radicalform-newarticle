<?php
/**
 * @package    Plg_Radicalform_NewArticle
 * @author     Dmitry Rekun <d.rekuns@gmail.com>
 * @copyright  Copyright (C) 2020 - 2023 JPathRu. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;

Log::addLogger(
	['text_file' => 'plg_radicalform_newarticle.php'],
	Log::ALL,
	['plg_radicalform_newarticle']
);

/**
 * Base plugin class.
 *
 * @since  1.0
 */
class PlgRadicalformNewarticle extends CMSPlugin
{
	/**
	 * Application object
	 *
	 * @var    CMSApplication
	 * @since  1.0
	 */
	protected $app;

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  1.0
	 */
	protected $db;

	/**
	 * Affects constructor behavior.
	 * If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Handles event when Radical Form sends data.
	 *
	 * @param   array     $formInput  Form input.
	 * @param   array     $input      Full input.
	 * @param   Registry  $params     Plugin parameters.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onBeforeSendRadicalForm($formInput, $input, $params)
	{
		$data = $formInput;

		$formID = trim($this->params->get('form_id', ''));

		if ($formID !== '' && $formID !== $input['rfFormID'])
		{
			return;
		}

		try
		{
			$this->createArticle($data);
		}
		catch (Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'plg_' . $this->_type . '_' . $this->_name);
		}
	}

	/**
	 * Creates an article.
	 *
	 * @param   array   $data  Form data to create an article.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	private function createArticle(array $data)
	{
		$contentPath = JPATH_ADMINISTRATOR . '/components/com_content';

		BaseDatabaseModel::addIncludePath($contentPath . '/models/', 'ContentModel');
		Form::addFormPath($contentPath . '/models/forms');
		Form::addFormPath($contentPath . '/model/form');
		Form::addFieldPath($contentPath . '/models/fields');
		Form::addFieldPath($contentPath . '/model/field');
		Form::addFormPath($contentPath . '/forms');

		/** @var ContentModelArticle $model */
		$model = BaseDatabaseModel::getInstance('Article', 'ContentModel');

		$titleName = trim($this->params->get('article_title'));
		$textName  = trim($this->params->get('article_text'));

		$title = !empty($data[$titleName]) ? $data[$titleName] : 'Dummy Title';
		$text  = !empty($data[$textName]) ? $data[$textName] : 'Dummy Text';

		$article = [
			'id'         => 0,
			'title'      => $title,
			'alias'      => '',
			'introtext'  => $text,
			'catid'      => (int) $this->params->get('catid', 0),
			'state'      => (int) $this->params->get('state', 0),
			'created_by' => (int) $this->params->get('created_by', 0),
			'language'   => '*',
			'access'     => Factory::getApplication()->get('access', 1)
		];

		$form = $model->getForm($article, false);

		if (!$form)
		{
			throw new \RuntimeException('Error getting form: ' . $model->getError());
		}

		if (!$model->validate($form, $article))
		{
			throw new \RuntimeException('Error validating article: ' . $model->getError());
		}

		Factory::getApplication()->input->set('task', 'save');

		if (!$model->save($article))
		{
			throw new \RuntimeException('Error saving article: ' . $model->getError());
		}
	}
}
