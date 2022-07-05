<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Doctrine\DBAL\Types\BinaryStringType;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\Types;

/**
 * Provide methods to handle versioning.
 */
class Versions extends Controller
{
	/**
	 * Table
	 * @var string
	 */
	protected $strTable;

	/**
	 * Parent ID
	 * @var integer
	 */
	protected $intPid;

	/**
	 * Edit URL
	 * @var string
	 */
	protected $strEditUrl;

	/**
	 * Username
	 * @var string
	 */
	protected $strUsername;

	/**
	 * User ID
	 * @var integer
	 */
	protected $intUserId;

	/**
	 * Initialize the object
	 *
	 * @param string  $strTable
	 * @param integer $intPid
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($strTable, $intPid)
	{
		$this->import(Database::class, 'Database');
		parent::__construct();

		$this->loadDataContainer($strTable);

		if (!isset($GLOBALS['TL_DCA'][$strTable]))
		{
			throw new \InvalidArgumentException(sprintf('"%s" is not a valid table', StringUtil::specialchars($strTable)));
		}

		$this->strTable = $strTable;
		$this->intPid = (int) $intPid;
	}

	/**
	 * Set the edit URL
	 *
	 * @param string $strEditUrl
	 */
	public function setEditUrl($strEditUrl)
	{
		$this->strEditUrl = $strEditUrl;
	}

	/**
	 * Set the username
	 *
	 * @param string $strUsername
	 */
	public function setUsername($strUsername)
	{
		$this->strUsername = $strUsername;
	}

	/**
	 * Set the user ID
	 *
	 * @param integer $intUserId
	 */
	public function setUserId($intUserId)
	{
		$this->intUserId = $intUserId;
	}

	/**
	 * Returns the latest version
	 *
	 * @return integer|null
	 */
	public function getLatestVersion()
	{
		if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] ?? null))
		{
			return null;
		}

		$objVersion = $this->Database->prepare("SELECT MAX(version) AS version FROM tl_version WHERE fromTable=? AND pid=?")
									 ->limit(1)
									 ->execute($this->strTable, $this->intPid);

		return (int) $objVersion->version;
	}

	/**
	 * Create the initial version of a record
	 */
	public function initialize()
	{
		if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] ?? null))
		{
			return;
		}

		$objVersion = $this->Database->prepare("SELECT COUNT(*) AS count FROM tl_version WHERE fromTable=? AND pid=?")
									 ->limit(1)
									 ->execute($this->strTable, $this->intPid);

		if ($objVersion->count > 0)
		{
			return;
		}

		$this->create(true);
	}

	/**
	 * Create a new version of a record
	 *
	 * @param boolean $blnHideUser
	 */
	public function create($blnHideUser=false)
	{
		if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] ?? null))
		{
			return;
		}

		// Get the new record
		$objRecord = $this->Database->prepare("SELECT * FROM " . $this->strTable . " WHERE id=?")
									->limit(1)
									->execute($this->intPid);

		if ($objRecord->numRows < 1 || $objRecord->tstamp < 1)
		{
			return;
		}

		$data = $objRecord->row();

		// Remove fields that are excluded from versioning
		foreach (array_keys($data) as $k)
		{
			if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['versionize'] ?? null) === false)
			{
				unset($data[$k]);
			}
		}

		$strDescription = '';

		if (!empty($data['title']))
		{
			$strDescription = $data['title'];
		}
		elseif (!empty($data['name']))
		{
			$strDescription = $data['name'];
		}
		elseif (!empty($data['firstname']))
		{
			$strDescription = $data['firstname'] . ' ' . $data['lastname'];
		}
		elseif (!empty($data['headline']))
		{
			$chunks = StringUtil::deserialize($data['headline']);

			if (\is_array($chunks) && isset($chunks['value']))
			{
				$strDescription = $chunks['value'];
			}
			else
			{
				$strDescription = $data['headline'];
			}
		}
		elseif (!empty($data['selector']))
		{
			$strDescription = $data['selector'];
		}
		elseif (!empty($data['subject']))
		{
			$strDescription = $data['subject'];
		}

		$strDescription = mb_substr($strDescription, 0, System::getContainer()->get('database_connection')->getSchemaManager()->listTableColumns('tl_version')['description']->getLength());

		$intId = $this->Database->prepare("INSERT INTO tl_version (pid, tstamp, version, fromTable, username, userid, description, editUrl, active, data) VALUES (?, ?, IFNULL((SELECT MAX(version) FROM (SELECT version FROM tl_version WHERE pid=? AND fromTable=?) v), 0) + 1, ?, ?, ?, ?, ?, 1, ?)")
								->execute($this->intPid, time(), $this->intPid, $this->strTable, $this->strTable, $blnHideUser ? null : $this->getUsername(), $blnHideUser ? 0 : $this->getUserId(), $strDescription, $this->getEditUrl(), serialize($data))
								->insertId;

		$this->Database->prepare("UPDATE tl_version SET active='' WHERE pid=? AND fromTable=? AND id!=?")
					   ->execute($this->intPid, $this->strTable, $intId);

		$intVersion = $this->Database->prepare("SELECT version FROM tl_version WHERE id=?")
									 ->execute($intId)
									 ->version;

		// Trigger the oncreate_version_callback
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['oncreate_version_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['oncreate_version_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($this->strTable, $this->intPid, $intVersion, $data);
				}
				elseif (\is_callable($callback))
				{
					$callback($this->strTable, $this->intPid, $intVersion, $data);
				}
			}
		}

		System::getContainer()->get('monolog.logger.contao.general')->info('Version ' . $intVersion . ' of record "' . $this->strTable . '.id=' . $this->intPid . '" has been created' . $this->getParentEntries($this->strTable, $this->intPid));
	}

	/**
	 * Restore a version
	 *
	 * @param integer $intVersion
	 */
	public function restore($intVersion)
	{
		if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] ?? null))
		{
			return;
		}

		$objData = $this->Database->prepare("SELECT * FROM tl_version WHERE fromTable=? AND pid=? AND version=?")
								  ->limit(1)
								  ->execute($this->strTable, $this->intPid, $intVersion);

		if ($objData->numRows < 1)
		{
			return;
		}

		$data = StringUtil::deserialize($objData->data);

		if (!\is_array($data))
		{
			return;
		}

		// Get the currently available fields
		$arrFields = array_flip($this->Database->getFieldNames($this->strTable));

		// Unset fields that do not exist (see #5219)
		$data = array_intersect_key($data, $arrFields);

		// Reset fields added after storing the version to their default value (see #7755)
		foreach (array_diff_key($arrFields, $data) as $k=>$v)
		{
			$data[$k] = Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['sql'] ?? array());
		}

		foreach ($data as $k=>$v)
		{
			// Remove fields that are excluded from versioning
			if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['versionize'] ?? null) === false)
			{
				unset($data[$k]);
				continue;
			}

			// Reset unique fields if the restored value already exists (see #698)
			if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['unique'] ?? null) === true)
			{
				$objResult = $this->Database->prepare("SELECT COUNT(*) AS cnt FROM " . $this->strTable . " WHERE " . Database::quoteIdentifier($k) . "=? AND id!=?")
											->execute($v, $this->intPid);

				if ($objResult->cnt > 0)
				{
					$data[$k] = Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['sql'] ?? array());
				}
			}
		}

		try
		{
			$this->Database->prepare("UPDATE " . $this->strTable . " %s WHERE id=?")
						   ->set($data)
						   ->execute($this->intPid);
		}
		catch (\Exception $e)
		{
			System::getContainer()
				->get('monolog.logger.contao.error')
				->error(sprintf('Could not restore version %d of %s.%d: %s.', $intVersion, $this->strTable, $this->intPid, $e->getMessage()))
			;

			Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['versionNotRestored'], $intVersion));
			Controller::reload();
		}

		$this->Database->prepare("UPDATE tl_version SET active='' WHERE fromTable=? AND pid=?")
					   ->execute($this->strTable, $this->intPid);

		$this->Database->prepare("UPDATE tl_version SET active=1 WHERE fromTable=? AND pid=? AND version=?")
					   ->execute($this->strTable, $this->intPid, $intVersion);

		// Trigger the onrestore_version_callback
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onrestore_version_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onrestore_version_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($this->strTable, $this->intPid, $intVersion, $data);
				}
				elseif (\is_callable($callback))
				{
					$callback($this->strTable, $this->intPid, $intVersion, $data);
				}
			}
		}

		// Trigger the deprecated onrestore_callback
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onrestore_callback'] ?? null))
		{
			trigger_deprecation('contao/core-bundle', '4.0', 'Using the "onrestore_callback" has been deprecated and will no longer work in Contao 5.0. Use the "onrestore_version_callback" instead.');

			foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onrestore_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($this->intPid, $this->strTable, $data, $intVersion);
				}
				elseif (\is_callable($callback))
				{
					$callback($this->intPid, $this->strTable, $data, $intVersion);
				}
			}
		}

		System::getContainer()->get('monolog.logger.contao.general')->info('Version ' . $intVersion . ' of record "' . $this->strTable . '.id=' . $this->intPid . '" has been restored' . $this->getParentEntries($this->strTable, $this->intPid));
	}

	/**
	 * Compare versions
	 *
	 * @param bool $blnReturnBuffer
	 *
	 * @return string
	 *
	 * @throws ResponseException
	 */
	public function compare($blnReturnBuffer=false)
	{
		$strBuffer = '';
		$arrVersions = array();
		$intTo = 0;
		$intFrom = 0;

		$objVersions = $this->Database->prepare("SELECT * FROM tl_version WHERE pid=? AND fromTable=? ORDER BY version DESC")
									  ->execute($this->intPid, $this->strTable);

		if ($objVersions->numRows < 2)
		{
			$strBuffer = '<p>There are no versions of ' . $this->strTable . '.id=' . $this->intPid . '</p>';
		}
		else
		{
			$intIndex = 0;
			$from = array();

			// Store the versions and mark the active one
			while ($objVersions->next())
			{
				if ($objVersions->active)
				{
					$intIndex = $objVersions->version;
				}

				$arrVersions[$objVersions->version] = $objVersions->row();
				$arrVersions[$objVersions->version]['info'] = $GLOBALS['TL_LANG']['MSC']['version'] . ' ' . $objVersions->version . ' (' . Date::parse(Config::get('datimFormat'), $objVersions->tstamp) . ') ' . $objVersions->username;
			}

			// To
			if (Input::post('to') && isset($arrVersions[Input::post('to')]))
			{
				$intTo = Input::post('to');
				$to = StringUtil::deserialize($arrVersions[Input::post('to')]['data']);
			}
			elseif (Input::get('to') && isset($arrVersions[Input::get('to')]))
			{
				$intTo = Input::get('to');
				$to = StringUtil::deserialize($arrVersions[Input::get('to')]['data']);
			}
			else
			{
				$intTo = $intIndex;
				$to = StringUtil::deserialize($arrVersions[$intTo]['data']);
			}

			// From
			if (Input::post('from') && isset($arrVersions[Input::post('from')]))
			{
				$intFrom = Input::post('from');
				$from = StringUtil::deserialize($arrVersions[Input::post('from')]['data']);
			}
			elseif (Input::get('from') && isset($arrVersions[Input::get('from')]))
			{
				$intFrom = Input::get('from');
				$from = StringUtil::deserialize($arrVersions[Input::get('from')]['data']);
			}
			elseif ($objVersions->numRows > $intIndex)
			{
				$intFrom = $objVersions->first()->version;
				$from = StringUtil::deserialize($arrVersions[$intFrom]['data']);
			}
			elseif ($intIndex > 1)
			{
				$intFrom = $intIndex - 1;
				$from = StringUtil::deserialize($arrVersions[$intFrom]['data']);
			}

			// Only continue if both version numbers are set
			if ($intTo > 0 && $intFrom > 0)
			{
				System::loadLanguageFile($this->strTable);

				// Get the order fields
				$objDcaExtractor = DcaExtractor::getInstance($this->strTable);
				$arrFields = $objDcaExtractor->getFields();
				$arrOrderFields = $objDcaExtractor->getOrderFields();

				// Find the changed fields and highlight the changes
				foreach ($to as $k=>$v)
				{
					if ($from[$k] != $to[$k])
					{
						if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['doNotShow'] ?? null) || ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['hideInput'] ?? null))
						{
							continue;
						}

						$blnIsBinary = false;

						if (isset($arrFields[$k]))
						{
							if (\is_array($arrFields[$k]))
							{
								// Detect binary fields using Doctrine's built-in types or Contao's BinaryStringType (see #3665)
								$blnIsBinary = \in_array($arrFields[$k]['type'] ?? null, array(BinaryType::class, BlobType::class, Types::BINARY, Types::BLOB, BinaryStringType::NAME), true);
							}
							else
							{
								$blnIsBinary = strncmp($arrFields[$k], 'binary(', 7) === 0 || strncmp($arrFields[$k], 'blob ', 5) === 0;
							}
						}

						// Decrypt the values
						if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['encrypt'] ?? null)
						{
							$to[$k] = Encryption::decrypt($to[$k]);
							$from[$k] = Encryption::decrypt($from[$k]);
						}

						if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['multiple'] ?? null) || \in_array($k, $arrOrderFields))
						{
							if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['csv']))
							{
								$delimiter = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['csv'];

								if (isset($to[$k]))
								{
									$to[$k] = preg_replace('/' . preg_quote($delimiter, ' ?/') . '/', $delimiter . ' ', $to[$k]);
								}

								if (isset($from[$k]))
								{
									$from[$k] = preg_replace('/' . preg_quote($delimiter, ' ?/') . '/', $delimiter . ' ', $from[$k]);
								}
							}
							else
							{
								// Convert serialized arrays into strings
								if (!\is_array($to[$k]) && \is_array(($tmp = StringUtil::deserialize($to[$k]))))
								{
									$to[$k] = $this->implodeRecursive($tmp, $blnIsBinary);
								}

								if (!\is_array($from[$k]) && \is_array(($tmp = StringUtil::deserialize($from[$k]))))
								{
									$from[$k] = $this->implodeRecursive($tmp, $blnIsBinary);
								}
							}
						}

						unset($tmp);

						// Convert binary UUIDs to their hex equivalents (see #6365)
						if ($blnIsBinary)
						{
							if (Validator::isBinaryUuid($to[$k]))
							{
								$to[$k] = StringUtil::binToUuid($to[$k]);
							}

							if (Validator::isBinaryUuid($from[$k]))
							{
								$from[$k] = StringUtil::binToUuid($from[$k]);
							}
						}

						// Convert date fields
						if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['rgxp'] ?? null) == 'date')
						{
							$to[$k] = Date::parse(Config::get('dateFormat'), $to[$k] ?: '');
							$from[$k] = Date::parse(Config::get('dateFormat'), $from[$k] ?: '');
						}
						elseif (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['rgxp'] ?? null) == 'time')
						{
							$to[$k] = Date::parse(Config::get('timeFormat'), $to[$k] ?: '');
							$from[$k] = Date::parse(Config::get('timeFormat'), $from[$k] ?: '');
						}
						elseif ($k == 'tstamp' || ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['rgxp'] ?? null) == 'datim')
						{
							$to[$k] = Date::parse(Config::get('datimFormat'), $to[$k] ?: '');
							$from[$k] = Date::parse(Config::get('datimFormat'), $from[$k] ?: '');
						}

						// Decode entities if the "decodeEntities" flag is not set (see #360)
						if (empty($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['decodeEntities']))
						{
							$to[$k] = StringUtil::decodeEntities($to[$k]);
							$from[$k] = StringUtil::decodeEntities($from[$k]);
						}

						// Convert strings into arrays
						if (!\is_array($to[$k]))
						{
							$to[$k] = explode("\n", $to[$k]);
						}

						if (!\is_array($from[$k]))
						{
							$from[$k] = explode("\n", $from[$k]);
						}

						$field = $k;

						if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['label']))
						{
							$field = \is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['label']) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['label'][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['label'];
						}
						elseif (isset($GLOBALS['TL_LANG']['MSC'][$k]))
						{
							$field = \is_array($GLOBALS['TL_LANG']['MSC'][$k]) ? $GLOBALS['TL_LANG']['MSC'][$k][0] : $GLOBALS['TL_LANG']['MSC'][$k];
						}

						$objDiff = new \Diff($from[$k], $to[$k]);
						$strBuffer .= $objDiff->render(new DiffRenderer(array('field'=>$field)));
					}
				}
			}
		}

		// Identical versions
		if (!$strBuffer)
		{
			$strBuffer = '<p>' . $GLOBALS['TL_LANG']['MSC']['identicalVersions'] . '</p>';
		}

		if ($blnReturnBuffer)
		{
			return $strBuffer;
		}

		$objTemplate = new BackendTemplate('be_diff');
		$objTemplate->content = $strBuffer;
		$objTemplate->versions = $arrVersions;
		$objTemplate->to = $intTo;
		$objTemplate->from = $intFrom;
		$objTemplate->showLabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['showDifferences']);
		$objTemplate->theme = Backend::getTheme();
		$objTemplate->base = Environment::get('base');
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['showDifferences']);
		$objTemplate->charset = System::getContainer()->getParameter('kernel.charset');

		throw new ResponseException($objTemplate->getResponse());
	}

	/**
	 * Render the versions drop-down menu
	 *
	 * @return string
	 */
	public function renderDropdown()
	{
		$objVersion = $this->Database->prepare("SELECT tstamp, version, username, active FROM tl_version WHERE fromTable=? AND pid=? ORDER BY version DESC")
									 ->execute($this->strTable, $this->intPid);

		if ($objVersion->numRows < 2)
		{
			return '';
		}

		$versions = '';

		while ($objVersion->next())
		{
			$versions .= '
  <option value="' . $objVersion->version . '"' . ($objVersion->active ? ' selected="selected"' : '') . '>' . $GLOBALS['TL_LANG']['MSC']['version'] . ' ' . $objVersion->version . ' (' . Date::parse(Config::get('datimFormat'), $objVersion->tstamp) . ') ' . $objVersion->username . '</option>';
		}

		return '
<div class="tl_version_panel">

<form id="tl_version" class="tl_form" method="post" aria-label="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['versioning']) . '">
<div class="tl_formbody">
<input type="hidden" name="FORM_SUBMIT" value="tl_version">
<input type="hidden" name="REQUEST_TOKEN" value="' . REQUEST_TOKEN . '">
<select name="version" class="tl_select">' . $versions . '
</select>
<button type="submit" name="showVersion" id="showVersion" class="tl_submit">' . $GLOBALS['TL_LANG']['MSC']['restore'] . '</button>
<a href="' . Backend::addToUrl('versions=1&amp;popup=1') . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['showDifferences']) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", sprintf($GLOBALS['TL_LANG']['MSC']['recordOfTable'], $this->intPid, $this->strTable))) . '\',\'url\':this.href});return false">' . Image::getHtml('diff.svg') . '</a>
</div>
</form>

</div>
';
	}

	/**
	 * Add a list of versions to a template
	 *
	 * @param BackendTemplate $objTemplate
	 */
	public static function addToTemplate(BackendTemplate $objTemplate)
	{
		$arrVersions = array();

		$objUser = BackendUser::getInstance();
		$params = $objUser->isAdmin ? array() : array($objUser->id);

		$objDatabase = Database::getInstance();

		// Get the total number of versions
		$objTotal = $objDatabase->prepare("SELECT COUNT(*) AS count FROM tl_version WHERE editUrl IS NOT NULL" . (!$objUser->isAdmin ? " AND userid=?" : ""))
								->execute(...$params);

		$intLast   = ceil($objTotal->count / 30);
		$intPage   = Input::get('vp') ?? 1;
		$intOffset = ($intPage - 1) * 30;

		// Validate the page number
		if ($intPage < 1 || ($intLast > 0 && $intPage > $intLast))
		{
			header('HTTP/1.1 404 Not Found');
		}

		// Create the pagination menu
		$objPagination = new Pagination($objTotal->count, 30, 7, 'vp', new BackendTemplate('be_pagination'));
		$objTemplate->pagination = $objPagination->generate();

		// Get the versions
		$objVersions = $objDatabase->prepare("SELECT pid, tstamp, version, fromTable, username, userid, description, editUrl, active FROM tl_version WHERE editUrl IS NOT NULL" . (!$objUser->isAdmin ? " AND userid=?" : "") . " ORDER BY tstamp DESC, pid, version DESC")
								   ->limit(30, $intOffset)
								   ->execute(...$params);

		$security = System::getContainer()->get('security.helper');

		while ($objVersions->next())
		{
			// Hide profile changes if the user does not have access to the "user" module (see #1309)
			if (!$objUser->isAdmin && $objVersions->fromTable == 'tl_user' && !$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'user'))
			{
				continue;
			}

			$arrRow = $objVersions->row();

			// Add some parameters
			$arrRow['from'] = max(($objVersions->version - 1), 1); // see #4828
			$arrRow['to'] = $objVersions->version;
			$arrRow['date'] = date(Config::get('datimFormat'), $objVersions->tstamp);
			$arrRow['description'] = StringUtil::substr($arrRow['description'], 32);
			$arrRow['shortTable'] = StringUtil::substr($arrRow['fromTable'], 18); // see #5769

			if (isset($arrRow['editUrl']))
			{
				// Adjust the edit URL of files in case they have been renamed (see #671)
				if ($arrRow['fromTable'] == 'tl_files' && ($filesModel = FilesModel::findByPk($arrRow['pid'])))
				{
					$arrRow['editUrl'] = preg_replace('/id=[^&]+/', 'id=' . $filesModel->path, $arrRow['editUrl']);
				}

				$arrRow['editUrl'] = preg_replace(array('/&(amp;)?popup=1/', '/&(amp;)?rt=[^&]+/'), array('', '&amp;rt=' . REQUEST_TOKEN), StringUtil::ampersand($arrRow['editUrl']));
			}

			$arrVersions[] = $arrRow;
		}

		$intCount = -1;
		$arrVersions = array_values($arrVersions);

		// Add the "even" and "odd" classes
		foreach ($arrVersions as $k=>$v)
		{
			$arrVersions[$k]['class'] = (++$intCount % 2 == 0) ? 'even' : 'odd';

			try
			{
				// Mark deleted versions (see #4336)
				$objDeleted = $objDatabase->prepare("SELECT COUNT(*) AS count FROM " . $v['fromTable'] . " WHERE id=?")
										  ->execute($v['pid']);

				$arrVersions[$k]['deleted'] = ($objDeleted->count < 1);
			}
			catch (\Exception $e)
			{
				// Probably a disabled module
				--$intCount;
				unset($arrVersions[$k]);
			}

			// Skip deleted files (see #8480)
			if (($v['fromTable'] ?? null) == 'tl_files' && ($arrVersions[$k]['deleted'] ?? null))
			{
				--$intCount;
				unset($arrVersions[$k]);
			}
		}

		$objTemplate->versions = $arrVersions;
	}

	/**
	 * Return the edit URL
	 *
	 * @return string
	 */
	protected function getEditUrl()
	{
		if ($this->strEditUrl !== null)
		{
			return sprintf($this->strEditUrl, $this->intPid);
		}

		$pairs = array();
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		parse_str($request->server->get('QUERY_STRING'), $pairs);

		// Adjust the URL of the "personal data" module (see #7987)
		if (isset($pairs['do']) && $pairs['do'] == 'login')
		{
			$pairs['do'] = 'user';
			$pairs['id'] = BackendUser::getInstance()->id;
			$pairs['rt'] = REQUEST_TOKEN;
		}

		if (isset($pairs['act']))
		{
			// Save the real edit URL if the visibility is toggled via Ajax
			if ($pairs['act'] == 'toggle')
			{
				$pairs['act'] = 'edit';
			}

			// Correct the URL in "edit|override multiple" mode (see #7745)
			if ($pairs['act'] == 'editAll' || $pairs['act'] == 'overrideAll')
			{
				$pairs['act'] = 'edit';
				$pairs['id'] = $this->intPid;
			}
		}

		return ltrim($request->getPathInfo(), '/') . '?' . http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);
	}

	/**
	 * Return the username
	 *
	 * @return string
	 */
	protected function getUsername()
	{
		if ($this->strUsername !== null)
		{
			return $this->strUsername;
		}

		$this->import(BackendUser::class, 'User');

		return $this->User->username;
	}

	/**
	 * Return the user ID
	 *
	 * @return string
	 */
	protected function getUserId()
	{
		if ($this->intUserId !== null)
		{
			return $this->intUserId;
		}

		$this->import(BackendUser::class, 'User');

		return $this->User->id;
	}

	/**
	 * Implode a multi-dimensional array recursively
	 *
	 * @param mixed   $var
	 * @param boolean $binary
	 *
	 * @return string
	 */
	protected function implodeRecursive($var, $binary=false)
	{
		if (!\is_array($var))
		{
			return $binary && Validator::isBinaryUuid($var) ? StringUtil::binToUuid($var) : $var;
		}

		if (!\is_array(current($var)))
		{
			if ($binary)
			{
				$var = array_map(static function ($v) { return Validator::isBinaryUuid($v) ? StringUtil::binToUuid($v) : $v; }, $var);
			}

			return implode(', ', $var);
		}

		$buffer = '';

		foreach ($var as $k=>$v)
		{
			$buffer .= $k . ": " . $this->implodeRecursive($v) . "\n";
		}

		return trim($buffer);
	}
}

class_alias(Versions::class, 'Versions');
