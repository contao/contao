<?php

namespace Contao\Fixtures;

class FileUpload
{
    public function generateMarkup()
    {
        return '<div class="uploader"></div>';
    }

    public function uploadTo()
    {
        return array('files/data.csv');
    }

    public function getReadableSize()
    {
        return '1 MB';
    }
}
