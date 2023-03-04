<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\Model;

use Contao\Model;

/**
 * Reads and writes OAuth clients.
 *
 * @property int    $id
 * @property int    $tstamp
 * @property string $title
 * @property string $type
 * @property string $client_id
 * @property string $client_secret
 * @property string $scope
 *
 * @method static NewsModel|null findById($id, array $opt=array())
 * @method static NewsModel|null findByPk($id, array $opt=array())
 * @method static NewsModel|null findOneBy($col, $val, array $opt=array())
 * @method static NewsModel|null findOneByPid($val, array $opt=array())
 * @method static NewsModel|null findOneByTstamp($val, array $opt=array())
 * @method static NewsModel|null findOneByTitle($val, array $opt=array())
 * @method static NewsModel|null findOneByType($val, array $opt=array()
 * @method static NewsModel|null findOneByClientId($val, array $opt=array())
 * @method static NewsModel|null findOneByClientSecret($val, array $opt=array())
 * @method static NewsModel|null findOneByScope($val, array $opt=array())
 * @method static Collection|NewsModel[]|NewsModel|null findByPid($val, array $opt=array())
 * @method static Collection|NewsModel[]|NewsModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|NewsModel[]|NewsModel|null findByTitle($val, array $opt=array())
 * @method static Collection|NewsModel[]|NewsModel|null findByType($val, array $opt=array())
 * @method static Collection|NewsModel[]|NewsModel|null findByClientId($val, array $opt=array())
 * @method static Collection|NewsModel[]|NewsModel|null findByClientSecret($val, array $opt=array())
 * @method static Collection|NewsModel[]|NewsModel|null findByScope($val, array $opt=array())
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByType($val, array $opt=array())
 * @method static integer countByClientId($val, array $opt=array())
 * @method static integer countByClientSecret($val, array $opt=array())
 * @method static integer countByScope($val, array $opt=array())
 */
class OAuthClientModel extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_oauth_client';
}
