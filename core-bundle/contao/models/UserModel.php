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

/**
 * Reads and writes users
 *
 * @property string|integer    $id
 * @property string|integer    $tstamp
 * @property string|null       $username
 * @property string            $name
 * @property string            $email
 * @property string            $language
 * @property string            $backendTheme
 * @property string|boolean    $fullscreen
 * @property string            $uploader
 * @property string|boolean    $showHelp
 * @property string|boolean    $thumbnails
 * @property string|boolean    $useRTE
 * @property string|boolean    $useCE
 * @property string            $password
 * @property string|boolean    $pwChange
 * @property string|boolean    $admin
 * @property string|array|null $groups
 * @property string            $inherit
 * @property string|array|null $modules
 * @property string|array|null $themes
 * @property string|array|null $elements
 * @property string|array|null $fields
 * @property string|array|null $pagemounts
 * @property string|array|null $alpty
 * @property string|array|null $filemounts
 * @property string|array|null $fop
 * @property string|array|null $imageSizes
 * @property string|array|null $forms
 * @property string|array|null $formp
 * @property string|array|null $amg
 * @property string|boolean    $disable
 * @property string|integer    $start
 * @property string|integer    $stop
 * @property string|array|null $session
 * @property string|integer    $dateAdded
 * @property string|null       $secret
 * @property string|boolean    $useTwoFactor
 * @property string|integer    $lastLogin
 * @property string|integer    $currentLogin
 * @property string|integer    $loginAttempts
 * @property string|integer    $locked
 * @property string|null       $backupCodes
 * @property string|integer    $trustedTokenVersion
 *
 * @method static UserModel|null findById($id, array $opt=array())
 * @method static UserModel|null findByPk($id, array $opt=array())
 * @method static UserModel|null findByIdOrAlias($val, array $opt=array())
 * @method static UserModel|null findByUsername($val, array $opt=array())
 * @method static UserModel|null findOneBy($col, $val, array $opt=array())
 * @method static UserModel|null findOneByTstamp($val, array $opt=array())
 * @method static UserModel|null findOneByName($val, array $opt=array())
 * @method static UserModel|null findOneByEmail($val, array $opt=array())
 * @method static UserModel|null findOneByLanguage($val, array $opt=array())
 * @method static UserModel|null findOneByBackendTheme($val, array $opt=array())
 * @method static UserModel|null findOneByFullscreen($val, array $opt=array())
 * @method static UserModel|null findOneByUploader($val, array $opt=array())
 * @method static UserModel|null findOneByShowHelp($val, array $opt=array())
 * @method static UserModel|null findOneByThumbnails($val, array $opt=array())
 * @method static UserModel|null findOneByUseRTE($val, array $opt=array())
 * @method static UserModel|null findOneByUseCE($val, array $opt=array())
 * @method static UserModel|null findOneByPassword($val, array $opt=array())
 * @method static UserModel|null findOneByPwChange($val, array $opt=array())
 * @method static UserModel|null findOneByAdmin($val, array $opt=array())
 * @method static UserModel|null findOneByGroups($val, array $opt=array())
 * @method static UserModel|null findOneByInherit($val, array $opt=array())
 * @method static UserModel|null findOneByModules($val, array $opt=array())
 * @method static UserModel|null findOneByThemes($val, array $opt=array())
 * @method static UserModel|null findOneByElements($val, array $opt=array())
 * @method static UserModel|null findOneByFields($val, array $opt=array())
 * @method static UserModel|null findOneByPagemounts($val, array $opt=array())
 * @method static UserModel|null findOneByAlpty($val, array $opt=array())
 * @method static UserModel|null findOneByFilemounts($val, array $opt=array())
 * @method static UserModel|null findOneByFop($val, array $opt=array())
 * @method static UserModel|null findOneByImageSizes($val, array $opt=array())
 * @method static UserModel|null findOneByForms($val, array $opt=array())
 * @method static UserModel|null findOneByFormp($val, array $opt=array())
 * @method static UserModel|null findOneByAmg($val, array $opt=array())
 * @method static UserModel|null findOneByDisable($val, array $opt=array())
 * @method static UserModel|null findOneByStart($val, array $opt=array())
 * @method static UserModel|null findOneByStop($val, array $opt=array())
 * @method static UserModel|null findOneBySession($val, array $opt=array())
 * @method static UserModel|null findOneByDateAdded($val, array $opt=array())
 * @method static UserModel|null findOneBySecret($val, array $opt=array())
 * @method static UserModel|null findOneByUseTwoFactor($val, array $opt=array())
 * @method static UserModel|null findOneByLastLogin($val, array $opt=array())
 * @method static UserModel|null findOneByCurrentLogin($val, array $opt=array())
 * @method static UserModel|null findOneByLoginAttempts($val, array $opt=array())
 * @method static UserModel|null findOneByLocked($val, array $opt=array())
 * @method static UserModel|null findOneByBackupCodes($val, array $opt=array())
 * @method static UserModel|null findOneByTrustedTokenVersion($val, array $opt=array())
 *
 * @method static Collection|UserModel[]|UserModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByName($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByEmail($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByLanguage($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByBackendTheme($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByFullscreen($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByUploader($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByShowHelp($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByThumbnails($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByUseRTE($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByUseCE($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByPassword($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByPwChange($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByAdmin($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByGroups($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByInherit($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByModules($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByThemes($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByElements($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByFields($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByPagemounts($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByAlpty($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByFilemounts($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByFop($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByImageSizes($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByForms($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByFormp($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByAmg($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByDisable($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByStart($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByStop($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findBySession($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByDateAdded($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findBySecret($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByUseTwoFactor($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByLastLogin($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByCurrentLogin($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByLoginAttempts($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByLocked($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByBackupCodes($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findByTrustedTokenVersion($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|UserModel[]|UserModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByUsername($val, array $opt=array())
 * @method static integer countByName($val, array $opt=array())
 * @method static integer countByEmail($val, array $opt=array())
 * @method static integer countByLanguage($val, array $opt=array())
 * @method static integer countByBackendTheme($val, array $opt=array())
 * @method static integer countByFullscreen($val, array $opt=array())
 * @method static integer countByUploader($val, array $opt=array())
 * @method static integer countByShowHelp($val, array $opt=array())
 * @method static integer countByThumbnails($val, array $opt=array())
 * @method static integer countByUseRTE($val, array $opt=array())
 * @method static integer countByUseCE($val, array $opt=array())
 * @method static integer countByPassword($val, array $opt=array())
 * @method static integer countByPwChange($val, array $opt=array())
 * @method static integer countByAdmin($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 * @method static integer countByInherit($val, array $opt=array())
 * @method static integer countByModules($val, array $opt=array())
 * @method static integer countByThemes($val, array $opt=array())
 * @method static integer countByElements($val, array $opt=array())
 * @method static integer countByFields($val, array $opt=array())
 * @method static integer countByPagemounts($val, array $opt=array())
 * @method static integer countByAlpty($val, array $opt=array())
 * @method static integer countByFilemounts($val, array $opt=array())
 * @method static integer countByFop($val, array $opt=array())
 * @method static integer countByImageSizes($val, array $opt=array())
 * @method static integer countByForms($val, array $opt=array())
 * @method static integer countByFormp($val, array $opt=array())
 * @method static integer countByAmg($val, array $opt=array())
 * @method static integer countByDisable($val, array $opt=array())
 * @method static integer countByStart($val, array $opt=array())
 * @method static integer countByStop($val, array $opt=array())
 * @method static integer countBySession($val, array $opt=array())
 * @method static integer countByDateAdded($val, array $opt=array())
 * @method static integer countBySecret($val, array $opt=array())
 * @method static integer countByUseTwoFactor($val, array $opt=array())
 * @method static integer countByLastLogin($val, array $opt=array())
 * @method static integer countByCurrentLogin($val, array $opt=array())
 * @method static integer countByLoginAttempts($val, array $opt=array())
 * @method static integer countByLocked($val, array $opt=array())
 * @method static integer countByBackupCodes($val, array $opt=array())
 * @method static integer countByTrustedTokenVersion($val, array $opt=array())
 */
class UserModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_user';
}

class_alias(UserModel::class, 'UserModel');
