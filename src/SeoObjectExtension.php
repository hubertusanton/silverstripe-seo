<?php

namespace Hubertusanton\SilverStripeSeo;

use DOMDocument;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\CMS\Controllers\RootURLController;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\i18n\i18n;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\CMS\Model\RedirectorPage;

/**
 * SeoObjectExtension extends SiteTree with functionality for helping content authors to
 * write good content for search engines, it uses the added var SEOPageSubject around
 * which the SEO score for the page is determined
 */
class SeoObjectExtension extends Extension
{
    use Configurable;

    /**
     * Specify page types that will not include the SEO tab
     *
     * @config
     * @var array
     */
    private static $excluded_page_types = [
        ErrorPage::class,
        RedirectorPage::class,
        VirtualPage::class
    ];

    /**
     * Return an array of Facebook Open Graph Types used in Meta tags
     *
     * @config
     * @var array
     **/
    private static $og_types = [
        'website' => 'Website',
        'article' => 'Article',
        'book'    => 'Book',
        'profile' => 'Profile',
        'music'   => 'Music',
        'video'   => 'Video'
    ];

    /**
     * Let the webmaster tag be edited by the CMS admin
     *
     * @config
     * @var boolean
     */
    private static $use_webmaster_tag = true;

    private static $db = [
        'SEOPageSubject' => 'Varchar(256)',
        'SEOSocialType' => 'Varchar',
        'SEOHideSocialData' => 'Boolean'
    ];

    private static $has_one = [
        'SEOSocialImage' => Image::class
    ];

    private static $casting = [
        'SEOSocialTitle' => 'Varchar',
        'SEOSocialLocale' => 'Varchar'
    ];

    public $score_criteria = array(
        'pagesubject_defined' => false,
        'pagesubject_in_title' => false,
        'pagesubject_in_firstparagraph' => false,
        'pagesubject_in_url' => false,
        'pagesubject_in_metadescription' => false,
        'numwords_content_ok' => false,
        'pagetitle_length_ok' => false,
        'content_has_links' => false,
        'page_has_images' => false,
        'content_has_subtitles' => false,
        'images_have_alt_tags' => false,
        'images_have_title_tags' => false,
    );

    public $seo_score = 0;

    public $seo_score_tips = '';


    /**
     * getSEOScoreTips.
     * Get array of tips translated in current locale
     *
     * @param none
     * @return array $score_criteria_tips Associative array with translated tips
     */
    public function getSEOScoreTips() {

        $score_criteria_tips = array(
            'pagesubject_defined' => _t('SEO.SEOScoreTipPageSubjectDefined', 'Page subject is not defined for page'),
            'pagesubject_in_title' => _t('SEO.SEOScoreTipPageSubjectInTitle', 'Page subject is not in the title of this page'),
            'pagesubject_in_firstparagraph' => _t('SEO.SEOScoreTipPageSubjectInFirstParagraph', 'Page subject is not present in the first paragraph of the content of this page'),
            'pagesubject_in_url' => _t('SEO.SEOScoreTipPageSubjectInURL', 'Page subject is not present in the URL of this page'),
            'pagesubject_in_metadescription' => _t('SEO.SEOScoreTipPageSubjectInMetaDescription', 'Page subject is not present in the meta description of the page'),
            'numwords_content_ok' => _t('SEO.SEOScoreTipNumwordsContentOk', 'The content of this page is too short and does not have enough words. Please create content of at least 300 words based on the Page subject.'),
            'pagetitle_length_ok' => _t('SEO.SEOScoreTipPageTitleLengthOk', 'The title of the page is not long enough and should have a length of at least 40 characters.'),
            'content_has_links' => _t('SEO.SEOScoreTipContentHasLinks', 'The content of this page does not have any (outgoing) links.'),
            'page_has_images' => _t('SEO.SEOScoreTipPageHasImages', 'The content of this page does not have any images.'),
            'content_has_subtitles' => _t('SEO.SEOScoreTipContentHasSubtitles', 'The content of this page does not have any subtitles'),
            'images_have_alt_tags' => _t('SEO.SEOScoreTipImagesHaveAltTags', 'All images on this page do not have alt tags'),
            'images_have_title_tags' => _t('SEO.SEOScoreTipImagesHaveTitleTags', 'All images on this page do not have title tags')
        );

        return $score_criteria_tips;
    }

    /**
     * updateCMSFields.
     * Update Silverstripe CMS Fields for SEO Module
     *
     * @param FieldList
     * @return none
     */
    public function updateCMSFields(FieldList $fields)
    {
        // exclude SEO tab from some pages
        $excluded = Config::inst()->get(self::class, 'excluded_page_types');

        if ($excluded) {
            if (in_array($this->owner->getClassName(), $excluded)) {
                return;
            }
        }

        Requirements::css('hubertusanton/silverstripe-seo:client/css/seo.css');
        Requirements::javascript('hubertusanton/silverstripe-seo:client/js/seo.js');

        // better do this below in some init method? :
        $this->getSEOScoreCalculation();
        $this->setSEOScoreTipsUL();

        // lets create a new tab on top
        $fields->addFieldsToTab(
            'Root.SEO',
            [
                LiteralField::create('googlesearchsnippetintro', '<h3>' . _t('SEO.SEOGoogleSearchPreviewTitle', 'Preview google search') . '</h3>'),
                LiteralField::create('googlesearchsnippet', '<div id="google_search_snippet"></div>'),
                LiteralField::create('siteconfigtitle', '<div id="ss_siteconfig_title">' . $this->owner->getSiteConfig()->Title . '</div>'),
            ]
        );

        // move Metadata field from Root.Main to SEO tab for visualising direct impact on search result
        $fields->removeFieldsFromTab(
            'Root.Main',
            [
                'Metadata',
                'SEOSocialType',
                'SEOHideSocialData',
                'SEOSocialImage'
            ]
        );

        $fields->addFieldsToTab(
            'Root.SEO',
            [
                TextareaField::create("MetaDescription", $this->owner->fieldLabel('MetaDescription'))
                    ->setRightTitle(
                        _t(
                            'SiteTree.METADESCHELP',
                            "Search engines use this content for displaying search results (although it will not influence their ranking)."
                        )
                    )
                    ->addExtraClass('help'),
                GoogleSuggestField::create("SEOPageSubject", _t('SEO.SEOPageSubjectTitle', 'Subject of this page (required to view this page SEO score)')),
                LiteralField::create('', '<div class="message notice"><p>' .
                    _t(
                        'SEO.SEOSaveNotice',
                        "After making changes save this page to view the updated SEO score"
                    ) . '</p></div>'),
                LiteralField::create('ScoreTitle', '<h4 class="seo_score">' . _t('SEO.SEOScore', 'SEO Score') . '</h4>'),
                LiteralField::create('Score', $this->getHTMLStars()),
                LiteralField::create('ScoreClear', '<div class="score_clear"></div>')
            ]
        );

        if ($this->checkPageSubjectDefined()) {
            $fields->addFieldToTab(
                'Root.SEO',
                LiteralField::create('SimplePageSubjectCheckValues', $this->getHTMLSimplePageSubjectTest())
            );
        }

        if ($this->seo_score < 12) {
            $fields->addFieldsToTab(
                'Root.SEO',
                [
                    LiteralField::create('ScoreTipsTitle', '<h4 class="seo_score">' . _t('SEO.SEOScoreTips', 'SEO Score Tips') . '</h4>'),
                    LiteralField::create('ScoreTips', $this->seo_score_tips)
                ]
            );
        }

        // Add Social settings
        $fields->addFieldToTab(
            'Root.SEO',
            ToggleCompositeField::create(
                'SEOSocialData',
                _t('SEO.SEOSocialData', "Social Data"),
                [
                    $this
                        ->getOwner()
                        ->dbObject('SEOHideSocialData')
                        ->scaffoldFormField()
                        ->setTitle(_t('SEO.SEOHideSocialDataDescription', 'Hide Social Data From Pages HTML?')),
                    DropdownField::create(
                        'SEOSocialType',
                        _t('SEO.SEOSocialType', 'Social Content Type')
                    )->setSource($this->config()->og_types),
                    UploadField::create(
                        'SEOSocialImage',
                        _t('SEO.SEOSocialImage', 'Image to share on Social Media')
                    )->setDescription(_t('SEO.SEODefaultImage', 'Defaults to featured image, if available'))
                ]
            )
        );
    }

    /**
     * getHTMLStars.
     * Get html of stars rating in CMS, maximum score is 12
     * threshold 2
     *
     * @param none
     * @return String $html
     */
    public function getHTMLStars() {

        $treshold_score = $this->seo_score - 2 < 0 ? 0 : $this->seo_score - 2;

        $num_stars   = intval(ceil($treshold_score) / 2);

        $num_nostars = 5 - $num_stars;

        $html = '<div id="fivestar-widget">';

        for ($i = 1; $i <= $num_stars; $i++) {
            $html .= '<div class="star on"></div>';
        }
        if ($treshold_score % 2) {
            $html .= '<div class="star on-half"></div>';
            $num_nostars--;
        }
        for ($i = 1; $i <= $num_nostars; $i++) {
            $html .= '<div class="star"></div>';
        }


        $html .= '</div>';
        return $html;
    }

    /**
     * Get the current title for this page (to load into social tags)
     * First try to get the MetaTitle (if the field is available), if
     * not, fall back to title
     *
     * @return string
     */
    public function getSEOSocialTitle()
    {
        // Try to use meta title field (if available)
        if (!empty($this->getOwner()->MetaTitle)) {
            return $this->getOwner()->MetaTitle;
        }

        return $this->getOwner()->Title;
    }

    /**
     * Get the current site locale.
     *
     * @return string
     */
    public function getSEOSocialLocale()
    {
        return i18n::get_locale();
    }

    /**
     * Attempt to find a suitable social image to use if one is not set.
     * By default try to see if this is a blog post and add the "Featured Image"
     *
     * @return Image
     */
    public function getSEOPreferedSocialImage()
    {
        $owner = $this->getOwner();
        $social_image = $owner->SEOSocialImage();

        if (!$social_image->exists() && $owner->hasMethod('FeaturedImage') && $owner->FeaturedImage()->exists()) {
            return $owner->FeaturedImage();
        }

        // Return the default expected result
        return $social_image;
    }

    /**
     * Hooks into MetaTags SiteTree method and adds additional
     * meta data for Sharing of this page on Social Media
     *
     * @return null
     */
    public function MetaTags(&$tags)
    {
        $tags .= $this->getOwner()->renderWith('Hubertusanton\\SilverStripeSeo\\Includes\\SocialTags');

        if (Config::inst()->get('SeoObjectExtension', 'use_webmaster_tag')) {
            $siteConfig = SiteConfig::current_site_config();
            $tags .= $siteConfig->GoogleWebmasterMetaTag . "\n";
        }
    }

    /**
     * Return a breadcrumb trail to this page. Excludes "hidden" pages
     * (with ShowInMenus=0). Adds extra microdata compared to
     *
     * @param int $maxDepth The maximum depth to traverse.
     * @param boolean $unlinked Do not make page names links
     * @param string $stopAtPageType ClassName of a page to stop the upwards traversal.
     * @param boolean $showHidden Include pages marked with the attribute ShowInMenus = 0
     * @return string The breadcrumb trail.
     */
    public function SeoBreadcrumbs($separator = '&raquo;', $addhome = true, $maxDepth = 20, $unlinked = false, $stopAtPageType = false, $showHidden = false) {
        $page = $this->owner;
        $pages = array();

        while(
            $page
            && (!$maxDepth || count($pages) < $maxDepth)
            && (!$stopAtPageType || $page->ClassName != $stopAtPageType)
        ) {
            if($showHidden || $page->ShowInMenus || ($page->ID == $this->owner->ID)) {
                $pages[] = $page;
            }

            $page = $page->Parent;
        }
        // add homepage;
        if($addhome){
            $pages[] = SiteTree::get_by_link(RootURLController::get_homepage_link());
        }

        $template = new SSViewer('SeoBreadcrumbsTemplate');

        return $template->process($this->owner->customise(new ArrayData(array(
            'BreadcrumbSeparator' => $separator,
            'AddHome' => $addhome,
            'Pages' => new ArrayList(array_reverse($pages))
        ))));
    }


    /**
     * getHTMLSimplePageSubjectTest.
     * Get html of tips for the Page Subject
     *
     * @param none
     * @return String $html
     */
    public function getHTMLSimplePageSubjectTest() {

        return $this->owner->renderWith('SimplePageSubjectTest');

    }

    /**
     * getSEOScoreCalculation.
     * Do SEO score calculation and set class Array score_criteria 12 corresponding assoc values
     * Also set class Integer seo_score with score 0-12 based on values which are true in score_criteria array
     * Do SEO score calculation and set class Array score_criteria 11 corresponding assoc values
     * Also set class Integer seo_score with score 0-12 based on values which are true in score_criteria array
     *
     * @param none
     * @return none, set class array score_criteria tips boolean
     */
    public function getSEOScoreCalculation() {

        $this->score_criteria['pagesubject_defined']            = $this->checkPageSubjectDefined();
        $this->score_criteria['pagesubject_in_title']           = $this->checkPageSubjectInTitle();
        $this->score_criteria['pagesubject_in_firstparagraph']  = $this->checkPageSubjectInFirstParagraph();
        $this->score_criteria['pagesubject_in_url']             = $this->checkPageSubjectInUrl();
        $this->score_criteria['pagesubject_in_metadescription'] = $this->checkPageSubjectInMetaDescription();
        $this->score_criteria['numwords_content_ok']            = $this->checkNumWordsContent();
        $this->score_criteria['pagetitle_length_ok']            = $this->checkPageTitleLength();
        $this->score_criteria['content_has_links']              = $this->checkContentHasLinks();
        $this->score_criteria['page_has_images']                = $this->checkPageHasImages();
        $this->score_criteria['content_has_subtitles']          = $this->checkContentHasSubtitles();
        $this->score_criteria['images_have_alt_tags']           = $this->checkImageAltTags();
        $this->score_criteria['images_have_title_tags']         = $this->checkImageTitleTags();


        $this->seo_score = intval(array_sum($this->score_criteria));
    }

    /**
     * setSEOScoreTipsUL.
     * Set SEO Score tips ul > li for SEO tips literal field, based on score_criteria
     *
     * @param none
     * @return none, set class string seo_score_tips with tips html
     */
    public function setSEOScoreTipsUL() {

        $tips = $this->getSEOScoreTips();
        $this->seo_score_tips = '<ul id="seo_score_tips">';
        foreach ($this->score_criteria as $index => $crit) {
            if (!$crit) {
                $this->seo_score_tips .= '<li>' . $tips[$index] . '</li>';
            }
        }
        $this->seo_score_tips .= '</ul>';

    }

    /**
     * checkContentHasSubtitles.
     * check if page Content has a h2's in it
     *
     * @param HTMLText $html String
     * @return DOMDocument Object
     */
    private function createDOMDocumentFromHTML($html = null) {

        if ($html != null) {
            libxml_use_internal_errors(true);
            $dom = new DOMDocument;
            $dom->loadHTML($html);
            libxml_clear_errors();
            libxml_use_internal_errors(false);
            return $dom;
        }
    }


    /**
     * checkPageSubjectInImageAlt.
     * Checks if image alt tags contain page subject
     *
     * @param none
     * @return boolean
     */
    public function checkPageSubjectInImageAltTags() {

        $html = $this->getPageContent();

        // for newly created page
        if ($html == '') {
            return false;
        }

        $dom = $this->createDOMDocumentFromHTML($html);

        $images = $dom->getElementsByTagName('img');

        foreach($images as $image){
            if($image->hasAttribute('alt') && $image->getAttribute('alt') != ''){
                if (preg_match('/' . preg_quote($this->owner->SEOPageSubject, '/') . '/i', $image->getAttribute('alt'))) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * checkImageAltTags.
     * Checks if images in content have alt tags
     *
     * @param none
     * @return boolean
     */
    private function checkImageAltTags() {

        $html = $this->getPageContent();

        // for newly created page
        if ($html == '') {
            return false;
        }

        $dom = $this->createDOMDocumentFromHTML($html);

        $images = $dom->getElementsByTagName('img');

        $imagesWithAltTags = 0;
        foreach($images as $image){
            if($image->hasAttribute('alt') && $image->getAttribute('alt') != ''){
                $imagesWithAltTags++;
            }
        }
        if($imagesWithAltTags == $images->length){
            return true;
        }

        return false;
    }



    /**
     * checkImageTitleTags.
     * Checks if images in content have title tags
     *
     * @param none
     * @return boolean
     */
    private function checkImageTitleTags() {

        $html = $this->getPageContent();

        // for newly created page
        if ($html == '') {
            return false;
        }

        $dom = $this->createDOMDocumentFromHTML($html);

        $images = $dom->getElementsByTagName('img');

        $imagesWithTitleTags = 0;
        foreach($images as $image){
            if($image->hasAttribute('title') && $image->getAttribute('title') != ''){
                //echo $image->getAttribute('title') . '<br>';
                $imagesWithTitleTags++;
            }
        }

        if($imagesWithTitleTags == $images->length){
            return true;
        }

        return false;
    }

    /**
     * checkPageSubjectDefined.
     * Checks if SEOPageSubject is defined
     *
     * @param none
     * @return boolean
     */
    private function checkPageSubjectDefined() {
        return (trim($this->owner->SEOPageSubject != '')) ? true : false;
    }

    /**
     * checkPageSubjectInTitle.
     * Checks if defined PageSubject is present in the Page Title
     *
     * @param none
     * @return boolean
     */
    public function checkPageSubjectInTitle() {
        if ($this->checkPageSubjectDefined()) {
            if (preg_match('/' . preg_quote($this->owner->SEOPageSubject, '/') . '/i', $this->owner->MetaTitle ?? '')) {
                return true;
            } elseif (preg_match('/' . preg_quote($this->owner->SEOPageSubject, '/') . '/i', $this->owner->Title ?? '')) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * checkPageSubjectInContent.
     * Checks if defined PageSubject is present in the Page Content
     *
     * @param none
     * @return boolean
     */
    public function checkPageSubjectInContent() {
        if ($this->checkPageSubjectDefined()) {
            if (preg_match('/' . preg_quote($this->owner->SEOPageSubject, '/') . '/i', $this->getPageContent())) {
                return true;
            }
            else {
                return false;
            }
        }
        return false;
    }

    /**
     * checkPageSubjectInFirstParagraph.
     * Checks if defined PageSubject is present in the Page Content's First Paragraph
     *
     * @param none
     * @return boolean
     */
    public function checkPageSubjectInFirstParagraph() {
        if ($this->checkPageSubjectDefined()) {
            $first_paragraph = $this->owner->dbObject('Content')->FirstParagraph();

            if (trim($first_paragraph != '')) {
                if (preg_match('/' . preg_quote($this->owner->SEOPageSubject, '/') . '/i', $first_paragraph)) {
                    return true;
                }
                else {
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * checkPageSubjectInUrl.
     * Checks if defined PageSubject is present in the Page URLSegment
     *
     * @param none
     * @return boolean
     */
    public function checkPageSubjectInUrl() {
        if ($this->checkPageSubjectDefined()) {

            $url_segment             = $this->owner->URLSegment;
            $pagesubject_url_segment = $this->owner->generateURLSegment($this->owner->SEOPageSubject);

            if (preg_match('/' . preg_quote($pagesubject_url_segment, '/') . '/i', $url_segment)) {
                return true;
            }
            else {
                return false;
            }
        }
        return false;

    }

    /**
     * checkPageSubjectInMetaDescription.
     * Checks if defined PageSubject is present in the Page MetaDescription
     *
     * @param none
     * @return boolean
     */
    public function checkPageSubjectInMetaDescription() {
        if ($this->checkPageSubjectDefined()) {

            if (preg_match('/' . preg_quote($this->owner->SEOPageSubject, '/') . '/i', $this->owner->MetaDescription)) {
                return true;
            }
            else {
                return false;
            }
        }
        return false;

    }

    /**
     * checkNumWordsContent.
     * Checks if the number of words of the Page Content is 250
     *
     * @param none
     * @return boolean
     */
    private function checkNumWordsContent() {
        return ($this->getNumWordsContent() > 250) ? true : false;
    }

    /**
     * checkPageTitleLength.
     * check if length of Title and SiteConfig.Title has a minimal of 40 chars
     *
     * @param none
     * @return boolean
     */
    private function checkPageTitleLength() {
        $site_title_length = strlen($this->owner->getSiteConfig()->Title ?? '');
        // 3 is length of divider, this could all be done better ...
        return (($this->getNumCharsTitle() + 3 + $site_title_length) >= 40) ? true : false;
    }

    /**
     * checkContentHasLinks.
     * check if page Content has a href's in it
     *
     * @param none
     * @return boolean
     */
    private function checkContentHasLinks() {

        $html = $this->getPageContent();

        // for newly created page
        if ($html == '') {
            return false;
        }

        $dom = $this->createDOMDocumentFromHTML($html);

        $elements = $dom->getElementsByTagName('a');
        return ($elements->length) ? true : false;

    }

    /**
     * checkPageHasImages.
     * check if page Content has a img's in it
     *
     * @param none
     * @return boolean
     */
    private function checkPageHasImages() {

        $html = $this->getPageContent();

        // for newly created page
        if ($html == '') {
            return false;
        }

        $dom = $this->createDOMDocumentFromHTML($html);

        $elements = $dom->getElementsByTagName('img');
        return ($elements->length) ? true : false;

    }




    /**
     * checkContentHasSubtitles.
     * check if page Content has a h2's in it
     *
     * @param none
     * @return boolean
     */
    private function checkContentHasSubtitles() {

        $html = $this->getPageContent();

        // for newly created page
        if ($html == '') {
            return false;
        }

        $dom = $this->createDOMDocumentFromHTML($html);
        $elements = $dom->getElementsByTagName('h2');

        return ($elements->length) ? true : false;

    }

    /**
     * getNumWordsContent.
     * get the number of words in the Page Content
     *
     * @param none
     * @return Integer Number of words in content
     */

    public function getNumWordsContent() {
        return str_word_count((Convert::xml2raw($this->getPageContent())));
    }

    /**
     * getNumCharsTitle.
     * get the number of characters in the Page Title
     *
     * @param none
     * @return Integer Number of chars of the title
     */
    public function getNumCharsTitle() {
        return strlen($this->owner->Title ?? '');
    }

    /**
     *   getPageContent
     *   function to get html content of page which SEO score is based on
     *   (we use the same info as gets back from $Layout in template)
     *
     */
    public function getPageContent()
    {
        static $cache = null;

        if ($cache === null) {
            $session = [];

            if ($request = Injector::inst()->get(HTTPRequest::class)) {
            if (Controller::has_curr()) {
                $request = Controller::curr()->getRequest();
                if ($request) {
                    $session = $request->getSession();
                }
            }

            $response = Director::test($this->owner->Link(), [], $session);

            if (!$response->isError()) {
                $cache = $response->getBody();
            } else {
                $cache = '';
            }
        }

        return $cache;
    }
}
