<?php

/**
 * Music bundle for Contao Open Source CMS
 *
 * @author    Christopher Brandt <christopher.brandt@numero2.de>
 * @license   LGPL
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MusicBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Contao\System;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;


#[AsContentElement('spotify', category: 'music')]
#[AsContentElement('apple', category: 'music')]
class MusicController extends AbstractContentElementController {


    /**
     * @var Contao\CoreBundle\Image\Studio\Studio
     */
    private Studio $imageStudio;

    /**
     * @var Symfony\Component\Filesystem\Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var Symfony\Contracts\Translation\TranslatorInterface
     */
    private TranslatorInterface $translator;


    public function __construct( Studio $imageStudio, Filesystem $filesystem, TranslatorInterface $translator ) {

        $this->imageStudio = $imageStudio;
        $this->filesystem = $filesystem;
        $this->translator = $translator;
    }


    /**
     * {@inheritdoc}
     */
    protected function getResponse( FragmentTemplate $template, ContentModel $model, Request $request ): Response {

        $sourceParameters = match ($type = $template->get('type')) {
            'spotify' => $this->getSpotify($template, $model),
            'apple' => $this->getApple($template, $model),
            default => throw new \InvalidArgumentException(\sprintf('Unknown music provider "%s".', $type)),
        };

        return $sourceParameters;
    }


    /**
     * Handle functionality for the spotify player
     *
     * @param Contao\CoreBundle\Twig\FragmentTemplate $template
     * @param Contao\ContentModel $model
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    protected function getSpotify(FragmentTemplate $template, ContentModel $model): Response {

        // try to fetch data from the spotify api
        $data = null;
        try {
            $client = HttpClient::create();
            $response = $client->request('GET', 'https://open.spotify.com/oembed?url='.$model->sourceId);
            $data = $response->toArray();
        } catch( Exception $e) {}

        if (!$data) {
            $template->set('error', $this->translator->trans('MSC.musicError.invalidLink', [], 'contao_default'));
            return $template->getResponse();
        }

        $link = $data['iframe_url'];

        // check if the source contains a video
        if (str_contains($link, '/video?')) {

            // when videos are disabled, use just audio
            if (!$model->spotifyVideo) {
                $link = str_replace("/video?", '?', $link);
            } else {
                $containsVideo = true;
            }
        }

        if( $model->musicSplashImage == true ) {

            // download thumbnail image if no image provided
            if( empty($model->musicSplashSRC) ) {

                $thumbnail = $data['thumbnail_url'];

                // create path where the image should be saved
                $cacheDir = System::getContainer()->getParameter('contao.image.target_dir');
                $cachePath = 'spotify/'.md5($thumbnail).'.jpg';
                $cachePath = $cacheDir.'/'.$cachePath;

                // check if the image already exists, if not download it
                if( !$this->filesystem->exists($cachePath) || filesize($cachePath) == 0 ) {

                    try {
                        // save the image from the url to the filesystem
                        $fileContent = @file_get_contents($thumbnail);

                        if( $fileContent ) {
                            $this->filesystem->dumpFile($cachePath, $fileContent);
                        }

                    } catch( Exception $e ) {
                        throw $this->createException('Could not safe image to filesystem. Are your permission correct?');
                    }

                }

                if( $this->filesystem->exists($cachePath) && filesize($cachePath) > 0 ) {

                    // get path to the image
                    $rootDir = System::getContainer()->getParameter('kernel.project_dir');
                    $src = Path::makeRelative($cachePath, $rootDir);

                    $figureBuilder = $this->imageStudio->createFigureBuilder();
                    $figureBuilder->from($src);

                    if( $model->size ?? null ) {
                        $figureBuilder->setSize($model->size);
                    }

                    $figure = $figureBuilder->buildIfResourceExists();

                    if( $figure ) {
                        $template->set('splash_image', $figure);
                    }
                }

            } else {

                $figureBuilder = $this->imageStudio->createFigureBuilder();
                $figureBuilder->fromUuid($model->musicSplashSRC);

                if( $model->size ?? null ) {
                    $figureBuilder->setSize($model->size);
                }

                $figure = $figureBuilder->buildIfResourceExists();

                if( $figure ) {
                    $template->set('splash_image', $figure);
                }

            }

        } else {
            $template->set('splash_image', null);
        }

        $containsVideo = false;

        $playerSize = match( $size = $model->musicPlayerSize ) {
            'big' => [$containsVideo ? '625' : '100%', '352'],
            'compact' => $containsVideo ? ['500', '280'] : ['100%', '152'],
            'custom' => StringUtil::deserialize($model->musicPlayerScale),
            default => throw new InvalidArgumentException(sprintf('Unknown player size "%s".', $size)),
        };

        $template->set('link', $link);
        $template->set('theme', 'theme='.$model->spotifyTheme);
        $template->set('playerWidth', $playerSize[0]);
        $template->set('playerHeight', $playerSize[1]);
        $template->set('caption', $model->caption);

        return $template->getResponse();
    }


    /**
     * Handle functionality for the apple player
     *
     * @param Contao\CoreBundle\Twig\FragmentTemplate $template
     * @param Contao\ContentModel $model
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    protected function getApple(FragmentTemplate $template, ContentModel $model): Response {

        $link = $model->sourceId;

        // Regex to extract the type of content (Music, Podcast, Song, etc.)
        $variantPattern = '/https?:\/\/[^\/]+\.com\/[a-z]{2}(?:-[a-z]{2})?\/([a-z0-9-]+)\//';
        $variant = preg_match($variantPattern, $link, $m) ? $m[1] : '';

        // Get the correct type for the embed by the type of content
        $embedType = '';
        if( $variant == 'album' || $variant == 'song' ) {

            $embedType = 'music';
        } elseif( $variant == 'podcast' ) {

            $embedType = 'podcasts';
        }

        // Regex to extract the id of the content
        $idPattern = '/https?:\/\/[^\/]+\.com\/[a-z]{2}(?:-[a-z]{2})?\/(?:[a-z0-9-]+\/[^\/]+\/|podcast\/[^\/]+\/)(id\d+(?:\?i(?:=|&#61;)\d+)?|\d+)(?=(?:[\/?#]|$))/';
        $id = preg_match($idPattern, $link, $m) ? $m[1] : '';

        if( $model->musicSplashImage == true ) {

            // download thumbnail image if no image provided
            if( empty($model->musicSplashSRC) ) {


                // Regex to get the main id of the content
                $formatPattern = '/^(?:id)?(\d+)(?:\?(?:i(?:=|&#61;)\d+))?$/';
                $formattedId = preg_match($formatPattern, $id, $m) ? $m[1] : '';

                $data = null;

                // lookup the data of the content with the itunes api
                try {
                    $client = HttpClient::create();
                    $response = $client->request('GET', 'https://itunes.apple.com/lookup?id='.$formattedId);
                    $data = $response->toArray();

                    // extract results from the response
                    if( $data ) {
                        $data = $data['results'][0];
                    }
                } catch( Exception $e) {}

                if (!$data) {
                    $template->set('error', $this->translator->trans('MSC.musicError.invalidLink', [], 'contao_default'));
                    return $template->getResponse();
                }

                $thumbnail = $data['artworkUrl600'];

                // create path where the image should be saved
                $cacheDir = System::getContainer()->getParameter('contao.image.target_dir');
                $cachePath = 'apple/'.md5($thumbnail).'.jpg';
                $cachePath = $cacheDir.'/'.$cachePath;

                // check if the image already exists, if not download it
                if( !$this->filesystem->exists($cachePath) || filesize($cachePath) == 0 ) {

                    try {
                        // save the image from the url to the filesystem
                        $fileContent = @file_get_contents($thumbnail);

                        if( $fileContent ) {
                            $this->filesystem->dumpFile($cachePath, $fileContent);
                        }

                    } catch( Exception $e ) {
                        throw $this->createException('Could not safe image to filesystem. Are your permission correct?');
                    }

                }

                if( $this->filesystem->exists($cachePath) && filesize($cachePath) > 0 ) {

                    // get path to image
                    $rootDir = System::getContainer()->getParameter('kernel.project_dir');
                    $src = Path::makeRelative($cachePath, $rootDir);

                    $figureBuilder = $this->imageStudio->createFigureBuilder();
                    $figureBuilder->from($src);

                    if( $model->size ?? null ) {
                        $figureBuilder->setSize($model->size);
                    }

                    $figure = $figureBuilder->buildIfResourceExists();

                    if( $figure ) {
                        $template->set('splash_image', $figure);
                    }
                }

            } else {

                $figureBuilder = $this->imageStudio->createFigureBuilder();
                $figureBuilder->fromUuid($model->musicSplashSRC);

                if( $model->size ?? null ) {
                    $figureBuilder->setSize($model->size);
                }

                $figure = $figureBuilder->buildIfResourceExists();

                if( $figure ) {
                    $template->set('splash_image', $figure);
                }

            }

        } else {
            $template->set('splash_image', null);
        }

        $template->set('embedType', $embedType);
        $template->set('id', $id);
        $template->set('variant', $variant);
        $template->set('theme', 'theme='.$model->appleTheme);

        // check if the audio link is valid

        $audioLink = sprintf('https://embed.%s.apple.com/de/%s/%s&amp;itscg=30200&amp;itsct=%s_box_player', $embedType, $variant, $id, $embedType);

        try {
            $client = HttpClient::create();
            $response = $client->request('GET', $audioLink);

            if( 200 !== $response->getStatusCode() ) {
                $template->set('error', $this->translator->trans('MSC.musicError.invalidLink', [], 'contao_default'));
                return $template->getResponse();
            }

        } catch( Exception $e) {
            $template->set('error', $this->translator->trans('MSC.musicError.invalidLink', [], 'contao_default'));
            return $template->getResponse();
        }

        $template->set('caption', $model->caption);

        return $template->getResponse();
    }
}
