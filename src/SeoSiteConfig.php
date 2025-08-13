<?php

namespace Hubertusanton\SilverStripeSeo;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Config\Config;
use SilverStripe\Core\Extension;

/**
 * SeoSiteConfig adds site-wide settings for SEO
 */
class SeoSiteConfig extends Extension
{
    private static $db = [
        'GoogleWebmasterMetaTag' => 'Varchar(512)'
    ];

    /**
     * updateCMSFields.
     * Update Silverstripe CMS Fields for SEO Module
     *
     * @param FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        if (Config::inst()->get(SeoObjectExtension::class, 'use_webmaster_tag')) {
            $fields->addFieldToTab(
                "Root.SEO",
                TextareaField::create(
                    "GoogleWebmasterMetaTag",
                    _t('SEO.SEOGoogleWebmasterMetaTag', 'Google webmaster meta tag')
                )->setRightTitle(_t(
                    'SEO.SEOGoogleWebmasterMetaTagRightTitle',
                    "Full Google webmaster meta tag For example &lt;meta name=\"google-site-verification\" content=\"hjhjhJHG12736JHGdfsdf\" /&gt;"
                ))
            );
        }
    }
}
