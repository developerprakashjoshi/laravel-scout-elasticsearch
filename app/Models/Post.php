<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'title',
        'content',
        'author',
        'published_at',
        'category',      // New field
        'sub_category',  // New field
        'tags',          // New field
        'status',        // New field
        'misc',
        'is_draft',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'author' => $this->author,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
                            'category' => $this->category ?? null,
        'misc' => $this->misc ?? 'others',
        'sub_category' => $this->sub_category ?? 'dms',
        'tags' => $this->tags ?? null,
        'status' => $this->status ?? null,
        'is_draft' => $this->is_draft ?? false,
            'title_suggest' => [
                'input' => [$this->title],
                'weight' => 1
            ],
        ];
    }

    /**
     * Get the name of the index associated with the model.
     *
     * @return string
     */
    public function searchableAs()
    {
        return 'posts';
        

    }

    /**
     * Determine if the model should be searchable.
     *
     * @return bool
     */
    public function shouldBeSearchable()
    {
        return true; // Always searchable
        // return $this->status === 'published'; // Only index published posts
        // return !$this->isDeleted() && $this->isApproved();
    }

    /**
     * Get the value used to index the model.
     *
     * @return mixed
     */
    public function getScoutKey()
    {
        return $this->id;
        // return $this->slug; // Use slug instead of ID
    }

    /**
     * Get the key name used to index the model.
     *
     * @return mixed
     */
    public function getScoutKeyName()
    {
        return 'id';
        // return 'slug'; // Use slug instead of ID
    }

    /**
     * Get the custom mapping for Elasticsearch.
     *
     * @return array
     */
    public function getSearchableMapping()
    {
        return [
            'properties' => [
                'id' => [
                    'type' => 'integer'
                ],
                'title' => [
                'type' => 'text',
                'analyzer' => 'standard',
                'fields' => [
                    'keyword' => [
                        'type' => 'keyword'
                    ]
                ]
            ],
            'title_suggest' => [
                'type' => 'completion',
                'analyzer' => 'simple',
                'preserve_separators' => true,
                'preserve_position_increments' => true,
                'max_input_length' => 50
            ],
                'content' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword'
                        ]
                    ]
                ],
                'author' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword'
                        ]
                    ]
                ],
                'misc' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword'
                        ]
                    ]
                ],
                'published_at' => [
                    'type' => 'date',
                    'format' => 'strict_date_optional_time||epoch_millis'
                ],
                'created_at' => [
                    'type' => 'date',
                    'format' => 'strict_date_optional_time||epoch_millis'
                ],
                'updated_at' => [
                    'type' => 'date',
                    'format' => 'strict_date_optional_time||epoch_millis'
                ],
                'misc' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword'
                        ]
                    ]
                ],
                'is_draft' => [
                    'type' => 'boolean'
                ],
            ]
        ];
    }
} 