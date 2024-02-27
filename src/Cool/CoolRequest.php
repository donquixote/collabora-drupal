<?php

namespace Drupal\collabora_online\Cool;

function getDiscovery($server) {
    $discoveryUrl = $server.'/hosting/discovery';
    $res = file_get_contents($discoveryUrl);
    return $res;
}

function getWopiSrcUrl($discovery_parsed, $mimetype) {
    if ($discovery_parsed === null || $discovery_parsed == false) {
        return null;
    }
    $result = $discovery_parsed->xpath(sprintf('/wopi-discovery/net-zone/app[@name=\'%s\']/action', $mimetype));
    if ($result && count($result) > 0) {
        return $result[0]['urlsrc'];
    }
    return null;
}

function strStartsWith($s, $ss) {
    $res = strrpos($s, $ss);
    return !is_bool($res) && $res == 0;
}

class CoolRequest {

    private $errorCode;

    const ERROR_MSG = [
        0 => 'Success',
        101 => 'GET Request not found',
        201 => 'Collabora Online server address is not valid',
        202 => 'Collabora Online server address scheme does not match the current page url scheme',
        203 => 'No able to retrieve the discovery.xml file from the Collabora Online server with the submitted address.',
        102 => 'The retrieved discovery.xml file is not a valid XML file',
        103 => 'The requested mime type is not handled',
        204 => 'Warning! You have to specify the scheme protocol too (http|https) for the server address.'
    ];

    public $wopiSrc;

    public function __construct() {
        $this->errorCode = 0;
        $this->wopiSrc = '';
    }

    public function errorString() {
        return $this->errorCode . ': ' . static::ERROR_MSG[$this->errorCode];
    }

    public function getWopiSource() {
        $_HOST_SCHEME = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $default_config = \Drupal::config('collabora_online.settings');
        $server = $default_config->get('collabora')['server'];
        $wopiClientServer = $default_config->get('collabora')['server'];
        if (!$wopiClientServer) {
            $this->errorCode = 201;
            return;
        }
        $wopiClientServer = trim($wopiClientServer);

        if (!strStartsWith($wopiClientServer, 'http')) {
            $this->errorCode = 204;
            return;
        }


        if (!strStartsWith($wopiClientServer, $_HOST_SCHEME . '://')) {
            $this->errorCode = 202;
            return;
        }

        $discovery = getDiscovery($wopiClientServer);
        if (!$discovery) {
            $this->errorCode = 203;
            return;
        }

        if (\PHP_VERSION_ID < 80000) {
            // This is deprecated and disabled by default in PHP 8.0
            $loadEntities = libxml_disable_entity_loader(true);
        }
        $discovery_parsed = simplexml_load_string($discovery);
        if (\PHP_VERSION_ID < 80000) {
            // This is deprecated and disabled by default in PHP 8.0
            libxml_disable_entity_loader($loadEntities);
        }
        if (!$discovery_parsed) {
            $this->errorCode = 102;
            return;
        }

        $this->wopiSrc = strval(getWopiSrcUrl($discovery_parsed, 'text/plain')[0]);
        //        print("wopiSrc ");
        //        var_export($this->wopiSrc);
        //        print("\n");
        if (!$this->wopiSrc) {
            $this->errorCode = 103;
            return;
        }
    }
}

?>
