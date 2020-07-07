<?php

namespace Contao\NewsBundle\Security\Voter;

final class BackendPermissions
{
    public const USER_CAN_EDIT_NEWS_ARCHIVE = 'contao_user.news';
    public const USER_CAN_CREATE_NEWS_ARCHIVES = 'contao_user.newp.create';
    public const USER_CAN_DELETE_NEWS_ARCHIVES = 'contao_user.newp.create';

    public const USER_CAN_EDIT_NEWS_FEED = 'contao_user.newsfeeds';
    public const USER_CAN_CREATE_NEWS_FEEDS = 'contao_user.newsfeedp.create';
    public const USER_CAN_DELETE_NEWS_FEEDS = 'contao_user.newsfeedp.create';
}
