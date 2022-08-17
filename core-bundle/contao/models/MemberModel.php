<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Model\Collection;
use Contao\Model\Registry;

/**
 * Reads and writes members
 *
 * @property integer           $id
 * @property integer           $tstamp
 * @property string            $firstname
 * @property string            $lastname
 * @property integer           $dateOfBirth
 * @property string            $gender
 * @property string            $company
 * @property string            $street
 * @property string            $postal
 * @property string            $city
 * @property string            $state
 * @property string            $country
 * @property string            $phone
 * @property string            $mobile
 * @property string            $fax
 * @property string            $email
 * @property string            $website
 * @property string            $language
 * @property string|array|null $groups
 * @property boolean           $login
 * @property string|null       $username
 * @property string            $password
 * @property boolean           $assignDir
 * @property string|null       $homeDir
 * @property boolean           $disable
 * @property string|integer    $start
 * @property string|integer    $stop
 * @property integer           $dateAdded
 * @property integer           $lastLogin
 * @property integer           $currentLogin
 * @property integer           $loginAttempts
 * @property integer           $locked
 * @property string|array|null $session
 * @property string|null       $secret
 * @property boolean           $useTwoFactor
 * @property string|null       $backupCodes
 * @property integer           $trustedTokenVersion
 *
 * @method static MemberModel|null findById($id, array $opt=array())
 * @method static MemberModel|null findByPk($id, array $opt=array())
 * @method static MemberModel|null findByIdOrAlias($val, array $opt=array())
 * @method static MemberModel|null findOneBy($col, $val, array $opt=array())
 * @method static MemberModel|null findByUsername($val, array $opt=array())
 * @method static MemberModel|null findOneByTstamp($val, array $opt=array())
 * @method static MemberModel|null findOneByFirstname($val, array $opt=array())
 * @method static MemberModel|null findOneByLastname($val, array $opt=array())
 * @method static MemberModel|null findOneByDateOfBirth($val, array $opt=array())
 * @method static MemberModel|null findOneByGender($val, array $opt=array())
 * @method static MemberModel|null findOneByCompany($val, array $opt=array())
 * @method static MemberModel|null findOneByStreet($val, array $opt=array())
 * @method static MemberModel|null findOneByPostal($val, array $opt=array())
 * @method static MemberModel|null findOneByCity($val, array $opt=array())
 * @method static MemberModel|null findOneByState($val, array $opt=array())
 * @method static MemberModel|null findOneByCountry($val, array $opt=array())
 * @method static MemberModel|null findOneByPhone($val, array $opt=array())
 * @method static MemberModel|null findOneByMobile($val, array $opt=array())
 * @method static MemberModel|null findOneByFax($val, array $opt=array())
 * @method static MemberModel|null findOneByEmail($val, array $opt=array())
 * @method static MemberModel|null findOneByWebsite($val, array $opt=array())
 * @method static MemberModel|null findOneByLanguage($val, array $opt=array())
 * @method static MemberModel|null findOneByGroups($val, array $opt=array())
 * @method static MemberModel|null findOneByLogin($val, array $opt=array())
 * @method static MemberModel|null findOneByPassword($val, array $opt=array())
 * @method static MemberModel|null findOneByAssignDir($val, array $opt=array())
 * @method static MemberModel|null findOneByHomeDir($val, array $opt=array())
 * @method static MemberModel|null findOneByDisable($val, array $opt=array())
 * @method static MemberModel|null findOneByStart($val, array $opt=array())
 * @method static MemberModel|null findOneByStop($val, array $opt=array())
 * @method static MemberModel|null findOneByDateAdded($val, array $opt=array())
 * @method static MemberModel|null findOneByLastLogin($val, array $opt=array())
 * @method static MemberModel|null findOneByCurrentLogin($val, array $opt=array())
 * @method static MemberModel|null findOneByLoginAttempts($val, array $opt=array())
 * @method static MemberModel|null findOneByLocked($val, array $opt=array())
 * @method static MemberModel|null findOneBySession($val, array $opt=array())
 * @method static MemberModel|null findOneBySecret($val, array $opt=array())
 * @method static MemberModel|null findOneByUseTwoFactor($val, array $opt=array())
 * @method static MemberModel|null findOneByBackupCodes($val, array $opt=array())
 * @method static MemberModel|null findOneByTrustedTokenVersion($val, array $opt=array())
 *
 * @method static Collection|MemberModel[]|MemberModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByFirstname($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByLastname($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByDateOfBirth($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByGender($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByCompany($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByStreet($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByPostal($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByCity($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByState($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByCountry($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByPhone($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByMobile($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByFax($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByEmail($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByWebsite($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByLanguage($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByGroups($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByLogin($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByPassword($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByAssignDir($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByHomeDir($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByDisable($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByStart($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByStop($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByDateAdded($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByLastLogin($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByCurrentLogin($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByLoginAttempts($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByLocked($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findBySession($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findBySecret($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByUseTwoFactor($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByBackupCodes($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findByTrustedTokenVersion($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|MemberModel[]|MemberModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByFirstname($val, array $opt=array())
 * @method static integer countByLastname($val, array $opt=array())
 * @method static integer countByDateOfBirth($val, array $opt=array())
 * @method static integer countByGender($val, array $opt=array())
 * @method static integer countByCompany($val, array $opt=array())
 * @method static integer countByStreet($val, array $opt=array())
 * @method static integer countByPostal($val, array $opt=array())
 * @method static integer countByCity($val, array $opt=array())
 * @method static integer countByState($val, array $opt=array())
 * @method static integer countByCountry($val, array $opt=array())
 * @method static integer countByPhone($val, array $opt=array())
 * @method static integer countByMobile($val, array $opt=array())
 * @method static integer countByFax($val, array $opt=array())
 * @method static integer countByEmail($val, array $opt=array())
 * @method static integer countByWebsite($val, array $opt=array())
 * @method static integer countByLanguage($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 * @method static integer countByLogin($val, array $opt=array())
 * @method static integer countByUsername($val, array $opt=array())
 * @method static integer countByPassword($val, array $opt=array())
 * @method static integer countByAssignDir($val, array $opt=array())
 * @method static integer countByHomeDir($val, array $opt=array())
 * @method static integer countByDisable($val, array $opt=array())
 * @method static integer countByStart($val, array $opt=array())
 * @method static integer countByStop($val, array $opt=array())
 * @method static integer countByDateAdded($val, array $opt=array())
 * @method static integer countByLastLogin($val, array $opt=array())
 * @method static integer countByCurrentLogin($val, array $opt=array())
 * @method static integer countByLoginAttempts($val, array $opt=array())
 * @method static integer countByLocked($val, array $opt=array())
 * @method static integer countBySession($val, array $opt=array())
 * @method static integer countBySecret($val, array $opt=array())
 * @method static integer countByUseTwoFactor($val, array $opt=array())
 * @method static integer countByBackupCodes($val, array $opt=array())
 * @method static integer countByTrustedTokenVersion($val, array $opt=array())
 */
class MemberModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_member';

	/**
	 * Find an active member by their e-mail-address and username
	 *
	 * @param string $strEmail    The e-mail address
	 * @param string $strUsername The username
	 * @param array  $arrOptions  An optional options array
	 *
	 * @return MemberModel|null The model or null if there is no member
	 */
	public static function findActiveByEmailAndUsername($strEmail, $strUsername=null, array $arrOptions=array())
	{
		$t = static::$strTable;
		$time = Date::floorToMinute();

		$arrColumns = array("$t.email=? AND $t.login=1 AND $t.disable=0 AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')");

		if ($strUsername !== null)
		{
			$arrColumns[] = "$t.username=?";
		}

		return static::findOneBy($arrColumns, array($strEmail, $strUsername), $arrOptions);
	}

	/**
	 * Find an unactivated member with a valid opt-in token by their e-mail-address
	 *
	 * @param string $strEmail   The e-mail address
	 * @param array  $arrOptions An optional options array
	 *
	 * @return static The model or null if there is no member
	 */
	public static function findUnactivatedByEmail($strEmail)
	{
		$t = static::$strTable;
		$objDatabase = Database::getInstance();

		$objResult = $objDatabase->prepare("SELECT * FROM $t WHERE email=? AND disable=1 AND EXISTS (SELECT * FROM tl_opt_in_related r LEFT JOIN tl_opt_in o ON r.pid=o.id WHERE r.relTable='$t' AND r.relId=$t.id AND o.createdOn>? AND o.confirmedOn=0)")
								 ->limit(1)
								 ->execute($strEmail, strtotime('-24 hours'));

		if ($objResult->numRows < 1)
		{
			return null;
		}

		$objRegistry = Registry::getInstance();

		/** @var MemberModel|Model $objMember */
		if ($objMember = $objRegistry->fetch($t, $objResult->id))
		{
			return $objMember;
		}

		return new static($objResult);
	}

	/**
	 * Find registrations that have not been activated for more than 24 hours
	 *
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|MemberModel[]|MemberModel|null A collection of models or null if there are no expired registrations
	 */
	public static function findExpiredRegistrations()
	{
		$t = static::$strTable;
		$objDatabase = Database::getInstance();

		$objResult = $objDatabase->prepare("SELECT * FROM $t WHERE disable=1 AND EXISTS (SELECT * FROM tl_opt_in_related r LEFT JOIN tl_opt_in o ON r.pid=o.id WHERE r.relTable='$t' AND r.relId=$t.id AND o.createdOn<=? AND o.confirmedOn=0)")
								 ->execute(strtotime('-24 hours'));

		if ($objResult->numRows < 1)
		{
			return null;
		}

		return static::createCollectionFromDbResult($objResult, $t);
	}

	/**
	 * Find an expired registration by email address that has not been activated for more than 24 hours
	 *
	 * @param string $strEmail The email address to find the expired registration for
	 *
	 * @return static The model or null if there is no expired registration
	 */
	public static function findExpiredRegistrationByEmail(string $strEmail)
	{
		$t = static::$strTable;
		$objDatabase = Database::getInstance();

		$objResult = $objDatabase->prepare("SELECT * FROM $t WHERE email=? AND disable=1 AND EXISTS (SELECT * FROM tl_opt_in_related r LEFT JOIN tl_opt_in o ON r.pid=o.id WHERE r.relTable='$t' AND r.relId=$t.id AND o.createdOn<=? AND o.confirmedOn=0)")
								 ->limit(1)
								 ->execute($strEmail, strtotime('-24 hours'));

		if ($objResult->numRows < 1)
		{
			return null;
		}

		$objRegistry = Registry::getInstance();

		/** @var MemberModel|Model $objMember */
		if ($objMember = $objRegistry->fetch($t, $objResult->id))
		{
			return $objMember;
		}

		return new static($objResult);
	}
}
