<?php
namespace Drupal\collabora_online\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\collabora_online\Cool\CoolRequest;
use Drupal\collabora_online\Cool\CoolUtils;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for the Collabora module.
 */
class ViewerController extends ControllerBase {

    private $renderer;

    /**
     * The controller constructor.
     */
    public function __construct(RendererInterface $renderer) {
        $this->renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): self {
        return new self(
            $container->get('renderer'),
        );
    }

    /**
     * Returns a raw page for the iframe embed..
     *
     * @return Response
     */
    public function editor(Media $media) {
        $default_config = \Drupal::config('collabora_online.settings');
        $wopiBase = $default_config->get('collabora')['wopi_base'];

        $req = new CoolRequest();
        $wopiClient = $req->getWopiClientURL();

        $id = $media->id();

        $accessToken = CoolUtils::tokenForFileId($id);

        $render_array = [
            'editor' => [
                '#wopiClient' => $wopiClient,
                '#wopiSrc' => urlencode($wopiBase . '/wopi/files/' . $id),
                '#accessToken' => $accessToken,
                '#theme' => 'collabora_online_full'
            ]
        ];
        $response = new Response();
        $response->setContent($this->renderer->renderRoot($render_array));

        return $response;
    }

    /**
     * Returns a simple page.
     *
     * @return array
     *   A simple renderable array.
     */
    public function view() {
        $default_config = \Drupal::config('collabora_online.settings');
        $wopiBase = $default_config->get('collabora')['wopi_base'];

        $req = new CoolRequest();
        $wopiClient = $req->getWopiClientURL();

        $coolUrl = $wopiClient . 'WOPISrc=' . urlencode($wopiBase . '/wopi/files/123');

        $accessToken = CoolUtils::tokenForFileId($id);

        return [
            '#theme' => 'collabora_online',
            '#wopiSrc' => $coolUrl,
            '#message1' => '<p>Hello from Collabora</p>' .
                '<p>We\'ll be loading from ' . $wopiClient . '</p>' .
                '<p>Error: ' . $req->errorString() . '</p>' .
                '<p>wopi base is set to ' . $wopiBase . '</p>',

        ];
    }
}

?>
