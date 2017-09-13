<?php

namespace SilverStripe\Blog\Widgets;

if (!class_exists('\\SilverStripe\\Widgets\\Model\\Widget')) {
    return;
}

use SilverStripe\Blog\Model\Blog;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Widgets\Model\Widget;

/**
 * @method Blog Blog()
 */
class BlogTagsCloudWidget extends Widget
{
    /**
     * @var string
     */
    private static $title = 'Tags Cloud';

    /**
     * @var string
     */
    private static $cmsTitle = 'Blog Tags Cloud';

    /**
     * @var string
     */
    private static $description = 'Displays a tag cloud for this blog.';

    /**
     * @var array
     */
    private static $db = array();

    /**
     * @var array
     */
    private static $has_one = array(
        'Blog' => Blog::class,
    );

    /**
     * {@inheritdoc}
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            /*
             * @var FieldList $fields
             */
            $fields->push(
                DropdownField::create(
                    'BlogID',
                    _t(__CLASS__ . '.Blog', 'Blog'),
                    Blog::get()->map()
                )
            );
        });

        return parent::getCMSFields();
    }

    /**
     * @return array
     */
    public function getTags()
    {
        if ($blog = $this->Blog()) {
            $escapedID = Convert::raw2sql($blog->ID);
            $sql = 'SELECT DISTINCT "BlogTag"."URLSegment","BlogTag"."Title",Count("BlogTagID") AS "TagCount"
				    from "BlogPost_Tags"
				    INNER JOIN "BlogPost"
				    ON "BlogPost"."ID" = "BlogPost_Tags"."BlogPostID"
				    INNER JOIN "BlogTag"
				    ON "BlogTag"."ID" = "BlogPost_Tags"."BlogTagID"
				    WHERE "BlogID" = ' . $escapedID
                . ' GROUP By  "BlogTag"."URLSegment","BlogTag"."Title"
				    ORDER BY "Title"';

            $records = DB::query($sql);
            $bloglink = $blog->Link();
            $maxTagCount = 0;

            // create DataObjects that can be used to render the tag cloud
            $tags = new ArrayList();
            foreach ($records as $record) {
                $tag = new DataObject();
                $tag->TagName = $record['Title'];
                $link = $bloglink.'tag/'.$record['URLSegment'];
                $tag->Link = $link;
                if ($record['TagCount'] > $maxTagCount) {
                    $maxTagCount = $record['TagCount'];
                }
                $tag->TagCount = $record['TagCount'];
                $tags->push($tag);
            }

            // normalize the tag counts from 1 to 10
            if ($maxTagCount) {
                $tagfactor = 10 / $maxTagCount;
                foreach ($tags->getIterator() as $tag) {
                    $normalized = round($tagfactor * ($tag->TagCount));
                    $tag->NormalizedTag = $normalized;
                }
            }


            return $tags;
        }

        return array();
    }
}
