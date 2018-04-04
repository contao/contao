<?php

namespace Contao\Fixtures;

use Contao\Model\Collection;

class PageModel
{
    private $data;
    private $index = -1;

    protected function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function findPublishedRootPages()
    {
        $page1 = new \stdClass();
        $page1->dns = '';
        $page1->fallback = '1';
        $page1->language = 'en';

        $page2 = new \stdClass();
        $page2->dns = 'test.com';
        $page2->fallback = '';
        $page2->language = 'en';

        return new Collection([$page1, $page2], 'tl_page');
    }

    public function __get($key)
    {
        return $this->data[$this->index]->$key;
    }

    public function next()
    {
        if (++$this->index >= count($this->data)) {
            return false;
        }

        return true;
    }
}
