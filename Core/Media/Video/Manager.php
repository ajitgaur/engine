<?php
/**
 * Video Manager
 */
namespace Minds\Core\Media\Video;

use Aws\S3\S3Client;
use Minds\Common;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use Minds\Entities\Video;

class Manager
{
    /** @var Config $config */
    private $config;

    /** @var S3Client $s3 */
    private $s3;

    public function __construct($config = null, $s3 = null)
    {
        $this->config = $config ?? Di::_()->get('Config');

        // AWS
        $awsConfig = $this->config->get('aws');
        $opts = [
            'region' => $awsConfig['region'],
        ];
        if (!isset($awsConfig['useRoles']) || !$awsConfig['useRoles']) {
            $opts['credentials'] = [
                'key' => $awsConfig['key'],
                'secret' => $awsConfig['secret'],
            ];
        }
        $this->s3 = $s3 ?: new S3Client(array_merge(['version' => '2006-03-01'], $opts));
    }

    /**
     * Return a public asset uri for entity type
     * @param Entity $entity
     * @param string $size
     * @return string
     */
    public function getPublicAssetUri($entity, $size = '360.mp4'): string
    {
        $cmd = null;
        switch (get_class($entity)) {
            case Activity::class:
                // To do
                break;
            case Video::class:
                $cmd = $this->s3->getCommand('GetObject', [
                    'Bucket' => 'cinemr', // TODO: don't hard code
                    'Key' => $this->config->get('transcoder')['dir'] . "/" . $entity->get('cinemr_guid') . "/" . $size,
                ]);
                break;
        }

        if (!$cmd) {
            return null;
        }
        if ($entity->access_id !== Common\Access::PUBLIC) {
            $url = (string)$this->s3->createPresignedRequest($cmd, '+48 hours')->getUri();
        } else {
            $url = $this->config->get('cinemr_url') . $entity->cinemr_guid . '/' . $size;
        }

        return $url;
    }
}