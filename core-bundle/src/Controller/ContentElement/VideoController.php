<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class VideoController extends AbstractContentElementController
{
    public function __construct(private Studio $studio)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $sourceParameters = match ($template->get('type')) {
            'vimeo' => $this->getVimeoSourceParameters($model),
            'youtube' => $this->getYoutubeSourceParameters($model, $request->getLocale()),
        };

        $template->set('source', $sourceParameters);

        $size = StringUtil::deserialize($model->playerSize, true);

        $template->set('width', $size[0] ?? 640);
        $template->set('height', $size[1] ?? 360);
        $template->set('aspect_ratio', $model->playerAspect);

        $template->set('caption', $model->playerCaption);

        $figure = $this->studio
            ->createFigureBuilder()
            ->from($model->singleSRC)
            ->setSize($model->size)
            ->buildIfResourceExists()
        ;

        $template->set('splash_image', $figure);

        return $template->getResponse();
    }

    /**
     * @return array<string, mixed>
     */
    private function getVimeoSourceParameters(ContentModel $model): array
    {
        $options = [];

        foreach (StringUtil::deserialize($model->vimeoOptions, true) as $option) {
            [$option, $value] = match ($option) {
                'vimeo_portrait', 'vimeo_title', 'vimeo_byline' => [substr($option, 6), '0'],
                default => [substr($option, 6), '1'],
            };

            $options[$option] = $value;
        }

        if ($color = $model->playerColor) {
            $option['color'] = $color;
        }

        $videoId = $model->vimeo;
        $baseUrl = "https://player.vimeo.com/video/$videoId";
        $query = http_build_query($options);

        if (($start = (int) $model->playerStart) > 0) {
            $option['start'] = $start;
            $query .= "#t={$start}s";
        }

        return [
            'video_id' => $videoId,
            'options' => $options,
            'base_url' => $baseUrl,
            'query' => $query,
            'url' => empty($query) ? $baseUrl : "$baseUrl?$query",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getYoutubeSourceParameters(ContentModel $model, string $locale): array
    {
        $options = [];
        $domain = 'https://www.youtube.com';

        foreach (StringUtil::deserialize($model->youtubeOptions, true) as $option) {
            if ('youtube_nocookie' === $option) {
                $domain = 'https://www.youtube-nocookie.com';

                continue;
            }

            [$option, $value] = match ($option) {
                'youtube_fs', 'youtube_rel', 'youtube_controls' => [substr($option, 8), '0'],
                'youtube_hl' => [substr($option, 8), \Locale::parseLocale($locale)[\Locale::LANG_TAG] ?? ''],
                'youtube_iv_load_policy' => [substr($option, 8), '3'],
                default => [substr($option, 8), '1'],
            };

            $options[$option] = $value;
        }

        if ($color = $model->playerColor) {
            $option['color'] = $color;
        }

        if (($start = (int) $model->playerStart) > 0) {
            $option['start'] = $start;
        }

        if (($end = (int) $model->playerStop) > 0) {
            $option['end'] = $end;
        }

        $videoId = $model->youtube;
        $baseUrl = "$domain/embed/$videoId";
        $query = http_build_query($options);

        return [
            'video_id' => $videoId,
            'options' => $options,
            'base_url' => $baseUrl,
            'query' => $query,
            'url' => empty($query) ? $baseUrl : "$baseUrl?$query",
        ];
    }
}
