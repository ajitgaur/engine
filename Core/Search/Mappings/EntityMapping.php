<?php

/**
 * Mapping for generic entity documents
 *
 * @author emi
 */

namespace Minds\Core\Search\Mappings;

class EntityMapping implements MappingInterface
{
    /** @var int */
    const MAX_HASHTAGS = 5;

    /** @var array $mappings */
    protected $mappings = [
        '@timestamp' => [ 'type' => 'date' ],
        'interactions' => [ 'type' => 'integer', '$exportField' => 'interactions' ],
        'guid' => [ 'type' => 'text', '$exportField' => 'guid' ],
        'type' => [ 'type' => 'text', '$exportField' => 'type' ],
        'subtype' => [ 'type' => 'text', '$exportField' => 'subtype' ],
        'taxonomy' => [ 'type' => 'text' ],
        'time_created' => [ 'type' => 'integer', '$exportField' => 'time_created' ],
        'access_id' => [ 'type' => 'text', '$exportField' => 'access_id' ],
        'public' => [ 'type' => 'boolean' ],
        'owner_guid' => [ 'type' => 'text', 'fielddata' => true, '$exportField' => 'owner_guid' ],
        'container_guid' => [ 'type' => 'text', 'fielddata' => true, '$exportField' => 'container_guid' ],
        'mature' => [ 'type' => 'boolean', '$exportField' => 'mature' ],
        'message' => [ 'type' => 'text', '$exportField' => 'message' ],
        'name' => [ 'type' => 'text', '$exportField' => 'name' ],
        'title' => [ 'type' => 'text', '$exportField' => 'title' ],
        'blurb' => [ 'type' => 'text', '$exportField' => 'blurb' ],
        'description' => [ 'type' => 'text', '$exportField' => 'description' ],
        'tags' => [ 'type' => 'text' ],
        'nsfw' => [ 'type' => 'integer' ],
        'paywall' => [ 'type' => 'boolean', '$exportField' => 'paywall' ],
        'wire_support_tier' => [ 'type' => 'text' ],
        'rating' => [ 'type' => 'integer', '$exportField' => 'rating' ],
        'moderator_guid' => [ 'type' => 'text'],
        '@moderated' => [ 'type' => 'date'],
    ];

    /** @var mixed $entity */
    protected $entity;

    /**
     * Sets the entity to be mapped
     *
     * @param mixed $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Gets the search type for the map
     * @return string
     * @throws \Exception
     */
    public function getType()
    {
        if (!$this->entity) {
            throw new \Exception('Entity is required');
        }

        $type = (string) $this->entity->type;

        if (isset($this->entity->subtype) && $this->entity->subtype) {
            $type .= ':' . $this->entity->subtype;
        }

        return $type;
    }

    /**
     * Gets the search id for the map
     * @return string
     * @throws \Exception
     */
    public function getId()
    {
        if (!$this->entity) {
            throw new \Exception('Entity is required');
        }

        return (string) $this->entity->guid;
    }

    /**
     * Get the ES mappings
     * @return array
     */
    public function getMappings()
    {
        return $this->mappings;
    }

    /**
     * Performs the mapping procedure
     * @param array $defaultValues
     * @return array
     * @throws \Exception
     */
    public function map(array $defaultValues = [])
    {
        if (!$this->entity) {
            throw new \Exception('Entity is required');
        }

        // Auto populate based on $exportField
        $map = array_merge($defaultValues, $this->autoMap());

        // Basics (taxonomy and timestamp)

        $taxonomy = [ $this->entity->type ];

        if (isset($this->entity->subtype) && $this->entity->subtype) {
            $taxonomy[] = $this->entity->subtype;
        }

        if (isset($this->entity->time_created) && $this->entity->time_created) {
            $map['@timestamp'] = $this->entity->time_created * 1000;
        }

        $map['taxonomy'] = implode(':', $taxonomy);

        // Public

        $map['public'] = !isset($this->entity->access_id) || $this->entity->access_id == ACCESS_PUBLIC;

        // Mature

        $mature = isset($map['mature']) && $map['mature'];

        if (method_exists($this->entity, 'getMature')) {
            $mature = $this->entity->getMature();
        } elseif (method_exists($this->entity, 'getFlag')) {
            $mature = $this->entity->getFlag('mature');
        }

        $map['mature'] = $mature;

        // Paywall

        $paywall = isset($map['paywall']) && $map['paywall'];

        if (method_exists($this->entity, 'isPaywall')) {
            $paywall = !!$this->entity->isPaywall();
        } elseif (method_exists($this->entity, 'getFlag')) {
            $paywall = !!$this->entity->getFlag('paywall');
        }

        $map['paywall'] = $paywall;

        // Support Tier

        $supportTier = null;

        if ($this->entity && ($this->entity->wire_threshold ?? null)) {
            $wireThreshold = is_string($this->entity->wire_threshold) ?
                json_decode($this->entity->wire_threshold, true) :
                $this->entity->wire_threshold;

            $supportTier = $wireThreshold['support_tier']['urn'] ?? null;
        }

        $map['wire_support_tier'] = $supportTier;

        // Text

        if (isset($map['description'])) {
            $map['description'] = strip_tags($map['description']);
        }

        // Hashags (should be the last)

        $fullText = '';

        if (isset($map['title'])) {
            $fullText .= ' ' . $map['title'];
        }

        if (isset($map['message'])) {
            $fullText .= ' ' . $map['message'];
        }

        if (isset($map['description'])) {
            $fullText .= ' ' . $map['description'];
        }

        $htRe = '/(^|\s||)#(\w*[a-zA-Z0-9_]+\w*)/';
        $matches = [];

        preg_match_all($htRe, $fullText, $matches);

        $messageTags = ($matches[2] ?? null) ?: [];
        $entityTags = method_exists($this->entity, 'getTags') ? ($this->entity->getTags() ?: []) : [];

        $tags = array_map(function ($tag) {
            return strtolower(trim($tag, " \t\n\r\0\x0B#"));
        }, array_merge($entityTags, $messageTags, $map['tags'] ?? []));

        $map['tags'] = array_slice(array_values(array_unique($tags)), 0, static::MAX_HASHTAGS);

        $map['nsfw'] = array_unique($this->entity->getNsfw());

        if (isset($this->entity->moderator_guid) && !empty($this->entity->moderator_guid)) {
            $map['moderator_guid'] = $this->entity->moderator_guid;
        }
        if (isset($this->entity->time_moderated) && $this->entity->time_moderated) {
            $map['@moderated'] = $this->entity->time_created * 1000;
        }

        //

        return $map;
    }

    /**
     * @param array $defaultValues
     * @return array
     */
    public function suggestMap(array $defaultValues = [])
    {
        return $defaultValues;
    }


    /**
     * @return array
     */
    protected function autoMap()
    {
        $map = [];

        foreach ($this->mappings as $key => $mapping) {
            if (!is_array($mapping) || !isset($mapping['$exportField'])) {
                continue;
            }

            $field = $mapping['$exportField'];
            $type = isset($mapping['type']) ? $mapping['type'] : 'text';

            if (isset($this->entity->{$field})) {
                switch ($type) {
                    case 'text':
                        $map[$key] = (string) $this->entity->{$field};
                        break;
                    case 'boolean':
                        $map[$key] = !!$this->entity->{$field};
                        break;
                    case 'integer':
                        $map[$key] = (int) $this->entity->{$field};
                        break;
                    default:
                        $map[$key] = $this->entity->{$field};
                }
            }
        }

        return $map;
    }
}
