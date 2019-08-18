<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Controllers\api\v2\feeds;

use Minds\Api\Exportable;
use Minds\Api\Factory;
use Minds\Common\Repository\Response;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Entities\Factory as EntitiesFactory;
use Minds\Entities\User;
use Minds\Interfaces;

class channel implements Interfaces\Api
{
    const MIN_COUNT = 6;

    public function get($pages)
    {
        /** @var User $currentUser */
        $currentUser = Core\Session::getLoggedinUser();

        //

        $container_guid = $pages[0] ?? null;

        if (!$container_guid) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Invalid container',
            ]);
        }

        $container = EntitiesFactory::build($container_guid);

        if (!$container || !Core\Security\ACL::_()->read($container, $currentUser)) {
            return Factory::response([
                'status' => 'error',
                'message' => 'Forbidden',
            ]);
        }

        $type = '';
        switch ($pages[1]) {
            case 'activities':
                $type = 'activity';
                break;
            case 'images':
                $type = 'object:image';
                break;
            case 'videos':
                $type = 'object:video';
                break;
            case 'blogs':
                $type = 'object:blog';
                break;
            case 'groups':
                $type = 'group';
                $container_guid = null;
                break;
            case 'all':
                $type = 'all';
                break;
        }

        //

        $hardLimit = 5000;
        $offset = 0;

        if (isset($_GET['offset'])) {
            $offset = intval($_GET['offset']);
        }

        $limit = 12;

        if (isset($_GET['limit'])) {
            $limit = abs(intval($_GET['limit']));
        }

        if (($offset + $limit) > $hardLimit) {
            $limit = $hardLimit - $offset;
        }

        if ($limit <= 0) {
            return Factory::response([
                'status' => 'success',
                'entities' => [],
                'load-next' => $hardLimit,
                'overflow' => true,
            ]);
        }

        //

        $sync = (bool) ($_GET['sync'] ?? false);

        $fromTimestamp = $_GET['from_timestamp'] ?? 0;

        $asActivities = (bool) ($_GET['as_activities'] ?? true);

        $forcePublic = (bool) ($_GET['force_public'] ?? false);

        $exclude = explode(',', $_GET['exclude'] ?? '');

        $query = null;

        if (isset($_GET['query'])) {
            $query = $_GET['query'];
        }

        $custom_type = isset($_GET['custom_type']) && $_GET['custom_type'] ? [$_GET['custom_type']] : null;

        /** @var Core\Feeds\Top\Entities $entities */
        $entities = new Core\Feeds\Top\Entities();
        $entities->setActor($currentUser);

        $isOwner = false;

        if ($currentUser) {
            $entities->setActor($currentUser);
            $isOwner = $currentUser->guid == $container_guid;
        }

        $opts = [
            'cache_key' => $currentUser ? $currentUser->guid : null,
            'container_guid' => $container_guid,
            'access_id' => $isOwner && !$forcePublic ? [0, 1, 2, $container_guid] : [2, $container_guid],
            'custom_type' => $custom_type,
            'limit' => $limit,
            'type' => $type,
            'algorithm' => 'top',
            'period' => '7d',
            'sync' => $sync,
            'from_timestamp' => $fromTimestamp,
            'query' => $query,
            'single_owner_threshold' => 0,
            'pinned_guids' => $type === 'activity' ? array_reverse($container->getPinnedPosts()) : null,
            'exclude' => $exclude,
        ];

        if (isset($_GET['nsfw'])) {
            $nsfw = $_GET['nsfw'] ?? '';
            $opts['nsfw'] = explode(',', $nsfw);
        }

        $hashtag = null;

        if (isset($_GET['hashtag'])) {
            $hashtag = strtolower($_GET['hashtag']);
        }

        if ($hashtag) {
            $opts['hashtags'] = [$hashtag];
            $opts['filter_hashtags'] = true;
        } elseif (isset($_GET['hashtags']) && $_GET['hashtags']) {
            $opts['hashtags'] = explode(',', $_GET['hashtags']);
            $opts['filter_hashtags'] = true;
        }

        try {
            $result = $this->getData($entities, $opts, $asActivities, $sync);

            if ($result->count() <= static::MIN_COUNT) {
                $opts['algorithm'] = 'latest';
                $result = $this->getData($entities, $opts, $asActivities, $sync);
            }

            return Factory::response([
                'status' => 'success',
                'entities' => Exportable::_($result),
                'load-next' => $result->getPagingToken(),
            ]);
        } catch (\Exception $e) {
            error_log($e);
            return Factory::response(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param Core\Feeds\Top\Entities $entities
     * @param array $opts
     * @param bool $asActivities
     * @param bool $sync
     * @return Response
     * @throws \Exception
     */
    private function getData($entities, $opts, $asActivities, $sync)
    {

        /** @var Core\Feeds\Top\Manager $manager */
        $manager = Di::_()->get('Feeds\Top\Manager');
        $result = $manager->getList($opts);

        if (!$sync) {
            // Remove all unlisted content, if ES document is not in sync, it'll
            // also remove pending activities
            $result = $result->filter([$entities, 'filter']);

            if ($asActivities) {
                // Cast to ephemeral Activity entities, if another type
                $result = $result->map([$entities, 'cast']);
            }
        }

        return $result;
    }

    public function post($pages)
    {
        return Factory::response([]);
    }

    public function put($pages)
    {
        return Factory::response([]);
    }

    public function delete($pages)
    {
        return Factory::response([]);
    }

}