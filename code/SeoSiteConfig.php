<?php
/**
 * SeoSiteConfig
 * adds site-wide settings for SEO
 */
class SeoSiteConfig extends DataExtension {

	private static $db = array(
		'GoogleWebmasterMetaTag' => 'Varchar(512)'
	);  


	/**
	 * updateCMSFields.
 	 * Update Silverstripe CMS Fields for SEO Module
 	 *  	 
	 * @param FieldList
	 * @return none
	 */
	public function updateCMSFields(FieldList $fields) {

        $fields->addFieldToTab("Root.SEO",
            TextareaField::create("GoogleWebmasterMetaTag", _t('SEO.SEOGoogleWebmasterMetaTag', 'Google webmaster meta tag'))
            ->setRightTitle(
                _t(
                    'SEO.SEOGoogleWebmasterMetaTagRightTitle',
                    "Full Google webmaster meta tag For example &lt;meta name=\"google-site-verification\" content=\"hjhjhJHG12736JHGdfsdf\" /&gt;"
                )
            )
        );

    }


}
