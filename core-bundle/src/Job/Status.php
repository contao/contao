<?php

namespace Contao\CoreBundle\Job;

enum Status: string
{
    case NEW = 'new';
    case PENDING = 'pending';
    case FINISHED = 'finished';
}
