<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Twig\Core\WebsiteRuntime;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * VideoRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class VideoRuntime implements RuntimeExtensionInterface
{
    private array $arguments = [];

    /**
     * VideoRuntime constructor.
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly WebsiteRuntime $websiteRuntime,
    ) {
    }

    /**
     * Get video view.
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function video(string $url, mixed $media = null, array $options = [], bool $onlyEmbedUrl = false): ?string
    {
        if (!$url || str_contains($url, '.mp4') || str_contains($url, '.vtt') || str_contains($url, '.webm')) {
            return $url;
        }

        $this->arguments = [];
        $this->arguments['url'] = $url;

        $website = $this->websiteRuntime->website();
        $this->arguments['autoplay'] = $options['autoplay'] ?? null;
        $this->arguments['loop'] = $options['loop'] ?? null;
        $this->arguments['thumbConfiguration'] = !empty($options['thumbConfiguration']) ? $options['thumbConfiguration'] : null;
        $this->arguments['axeptioId'] = $website->api->custom->axeptioId;
        $this->arguments['axeptioExternal'] = $website->api->custom->axeptioExternal;
        $this->arguments['gdprActive'] = $this->websiteRuntime->moduleActive('gdpr', $website->configuration)
            || $this->arguments['axeptioId'] || $this->arguments['axeptioExternal'];

        if (str_contains($url, 'youtube') || str_contains($url, 'youtu.be')) {
            $this->getYoutube($url);
        } elseif (str_contains($url, 'vimeo')) {
            $this->getVimeo($url);
        } elseif (str_contains($url, 'dailymotion')) {
            $this->getDailymotion($url);
        } elseif (str_contains($url, 'facebook') || str_contains($url, 'fb.watch')) {
            $this->getFacebook($url);
        }

        if ($onlyEmbedUrl && !empty($this->arguments['embed'])) {
            $embed = $this->arguments['embed'];
            if (!empty($this->arguments['videoStart'])) {
                $embed .= '?start='.$this->arguments['videoStart'];
            }

            return $embed;
        }

        if (!empty($this->arguments['embed'])) {
            $this->arguments['media'] = $media;
            $this->arguments['website'] = $website;
            $this->arguments = array_merge($this->arguments, $options);
            $this->arguments['prototype'] = $this->twig->render('gdpr/services/video-prototype.html.twig', $this->arguments);
            $this->arguments['prototype_placeholder'] = $this->twig->render('gdpr/services/video-prototype-placeholder.html.twig', $this->arguments);
            echo $this->twig->render('gdpr/services/video.html.twig', $this->arguments);
        }

        return null;
    }

    /**
     * Get Youtube arguments.
     */
    private function getYoutube(string $url): void
    {
        $this->arguments['videoStart'] = null;
        $this->arguments['videoID'] = $videoID = null;
        $this->arguments['player'] = 'youtube';
        if ($url && str_contains($url, 'watch')) {
            $matches = explode('&', $url);
            foreach ($matches as $match) {
                if ($match && str_contains($match, 'watch')) {
                    $explode = explode('watch?v=', $match);
                    $this->arguments['videoID'] = $videoID = end($explode);
                    break;
                }
            }
        } elseif (preg_match('/youtu.be/', $url) || $url && str_contains($url, '/embed/') || $url && str_contains($url, '/live/')) {
            $matches = explode('/', $url);
            $this->arguments['videoID'] = $videoID = end($matches);
        }
        if ($videoID && str_contains($videoID, '?')) {
            $matches = explode('?', $videoID);
            $this->arguments['videoID'] = $videoID = $matches[0];
        }
        if ($url && str_contains($url, 't=')) {
            if (str_contains($url, '&')) {
                $matches = explode('&', $url);
                foreach ($matches as $match) {
                    if ($match && str_contains($match, 't=')) {
                        $this->arguments['videoStart'] = str_replace(['t=', 's'], '', $match);
                        break;
                    }
                }
            } else {
                $matches = explode('t=', $videoID);
                $this->arguments['videoStart'] = str_replace(['t=', 's'], '', end($matches));
            }
        }
        $this->arguments['embed'] = 'https://youtube-nocookie.com/embed/'.$videoID;
    }

    /**
     * Get Vimeo arguments.
     */
    private function getVimeo(string $url): void
    {
        $this->arguments['player'] = 'vimeo';
        $matches = explode('/', $url);
        $videoID = end($matches);
        $this->arguments['embed'] = 'https://player.vimeo.com/video/'.$videoID;
    }

    /**
     * Get Dailymotion arguments.
     */
    private function getDailymotion(string $url): void
    {
        $this->arguments['player'] = 'dailymotion';
        $matches = explode('/', $url);
        $videoID = end($matches);
        $this->arguments['embed'] = 'https://www.dailymotion.com/embed/video/'.$videoID;
    }

    /**
     * Get Facebook arguments.
     */
    private function getFacebook(string $url): void
    {
        $this->arguments['player'] = 'facebook';
        $this->arguments['embed'] = 'https://www.facebook.com/plugins/video.php?height=314&href='.$url.'&show_text=false&width=560';
    }
}
