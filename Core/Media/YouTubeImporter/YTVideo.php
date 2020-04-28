<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Media\YouTubeImporter;

use Minds\Entities\User;
use Minds\Entities\Video;
use Minds\Traits\MagicAttributes;

/**
 * Class YTVideo
 * @package Minds\Core\Media\YouTubeImporter
 * @method string getVideoId()
 * @method YTVideo setVideoId(string $value)
 * @method string getChannelId()
 * @method YTVideo setChannelId(string $value)
 * @method string getYoutubeCreationDate()
 * @method YTVideo setYoutubeCreationDate(string $value)
 * @method Video getEntity()
 * @method YTVideo setEntity(Video $value)
 * @method string getOwnerGuid()
 * @method YTVideo setOwnerGuid(string $value)
 * @method \ElggEntity getOwner()
 * @method YTVideo setOwner(\ElggEntity $value)
 * @method string getStatus()
 * @method YTVideo setStatus(string $value)
 * @method string getTitle()
 * @method YTVideo setTitle(string $value)
 * @method string getDescription()
 * @method YTVideo setDescription(string $value)
 * @method string getDuration()
 * @method YTVideo setDuration(string $value)
 * @method string getLikes()
 * @method YTVideo setLikes(string $value)
 * @method string getDislikes()
 * @method YTVideo setDislikes(string $value)
 * @method string getFavorites()
 * @method YTVideo setFavorites(string $value)
 * @method string getViews()
 * @method YTVideo setViews(string $value)
 * @method array getFormat()
 * @method YTVideo setFormat(string $value)
 * @method string getThumbnail()
 * @method YTVideo setThumbnail(string $value)
 */
class YTVideo
{
    use MagicAttributes;

    /** @var string */
    protected $videoId;
    /** @var string */
    protected $channelId;
    /** @var string */
    protected $youtubeUrl;
    /** @var string */
    protected $youtubeCreationDate;
    /** @var Video */
    protected $entity;
    /** @var string */
    protected $ownerGuid;
    /** @var User */
    protected $owner;
    /** @var string */
    protected $status;
    /** @var string */
    protected $title;
    /** @var string */
    protected $description;
    /** @var int */
    protected $duration;
    /** @var string */
    protected $likes;
    /** @var string */
    protected $dislikes;
    /** @var string */
    protected $favorites;
    /** @var string */
    protected $views;
    /** @var array */
    protected $format;
    /** @var string */
    protected $thumbnail;

    public function getYoutubeUrl(): string
    {
        return "https://www.youtube.com/watch?v={$this->getVideoId()}";
    }

    public function export()
    {
        $export = [
            'video_id' => $this->videoId,
            'channel_id' => $this->channelId,
            'youtubeUrl' => $this->getYoutubeUrl(),
            'youtubeCreationDate' => $this->youtubeCreationDate,
            'entity' => $this->entity ? $this->entity->export() : null,
            'status' => $this->status,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'likes' => $this->likes,
            'dislikes' => $this->dislikes,
            'favorites' => $this->favorites,
            'views' => $this->views,
            'thumbnail' => $this->thumbnail,
            'ownerGuid' => $this->ownerGuid,
            'owner' => $this->owner ? $this->owner->export() : null,
        ];

        return $export;
    }
}
