<?php

class SeoObjectExtension extends SiteTreeExtension {


    public static $db = array(
        'SEOPageSubject' => 'Varchar(256)'
    );  


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
        'content_has_subtitles' => false
    );

    public $seo_score = 0;

    public $seo_score_tips = '';

    protected function isValueIterable($value) {
        return is_array($value) || $value instanceof DataObjectSet;
    }


    public function getSEOScoreTips() {
    
        $score_criteria_tips = array(
            'pagesubject_defined' => _t('SEO.SEOScoreTipPageSubjectDefined', 'Page subject is not defined for page'),
            'pagesubject_in_title' => _t('SEO.SEOScoreTipPageSubjectInTitle', 'Page subject is not in the title of this page'),
            'pagesubject_in_firstparagraph' => _t('SEO.SEOScoreTipPageSubjectInFirstParagraph', 'Page subject is not present in the first paragraph of the content of this page'),
            'pagesubject_in_url' => _t('SEO.SEOScoreTipPageSubjectInURL', 'Page subject is not present in the URL of this page'),
            'pagesubject_in_metadescription' => _t('SEO.SEOScoreTipPageSubjectInMetaDescription', 'Page subject is not present in the meta description of the page'),
            'numwords_content_ok' => _t('SEO.SEOScoreTipNumwordsContentOk', 'The content of this page is too short and does not have enough words. Please create content of at least 300 words based on the Page subject.'),      
            'pagetitle_length_ok' => _t('SEO.SEOScoreTipPageTitleLengthOk', 'The title of the page is not long enough and should have a lenght of at least 40 characters.'),
            'content_has_links' => _t('SEO.SEOScoreTipContentHasLinks', 'De content of this page does not have any (outgoing) links.'),
            'page_has_images' => _t('SEO.SEOScoreTipPageHasImages', 'The content of this page does not have any images.'),
            'content_has_subtitles' => _t('SEO.SEOScoreTipContentHasSubtitles', 'The content of this page does not have any subtitles')
        );
            
        return $score_criteria_tips;
    }
    
    
    
	/**
	 * @param FieldList
	 */
	public function updateCMSFields(FieldList $fields) {

        Requirements::css(SEO_DIR.'/css/seo.css');
        Requirements::javascript(SEO_DIR.'/javascript/seo.js');

        // better do this below in some init method? :
        $this->getSEOScoreCalculation(); 
        $this->setSEOScoreTipsUL();

        // lets create a new tab on top
        $fields->addFieldsToTab('Root.SEO', array(
            LiteralField::create('googlesearchsnippetintro', '<h3>' . _t('SEO.SEOGoogleSearchPreviewTitle', 'Preview google search') . '</h3>'),
            LiteralField::create('googlesearchsnippet', '<div id="google_search_snippet"></div>'),
            LiteralField::create('siteconfigtitle', '<div id="ss_siteconfig_title">' . $this->owner->getSiteConfig()->Title . '</div>'),
            
        ));

        // move Metadata field from Root.Main to SEO tab for visualising direct impact on search result
        $fields->removeByName('Metadata');

        $fields->addFieldToTab("Root.SEO", new TabSet('Options', 
            new Tab('Metadata', _t('SEO.SEOMetaData', 'Meta Data')), 
            new Tab('HelpAndSEOScore',  _t('SEO.SEOHelpAndScore', 'Help and SEO Score'))   
        )); 


        $fields->addFieldsToTab('Root.SEO.Options.Metadata', array(
                            TextField::create("MetaTitle", $this->owner->fieldLabel('MetaTitle')),
                            TextareaField::create("MetaKeywords", $this->owner->fieldLabel('MetaKeywords'), 1),
                            TextareaField::create("MetaDescription", $this->owner->fieldLabel('MetaDescription')),
                            TextareaField::create("ExtraMeta",$this->owner->fieldLabel('ExtraMeta'))
            )
        );
        $fields->addFieldsToTab('Root.SEO.Options.HelpAndSEOScore', array(
            LiteralField::create('ScoreTitle', '<h4 class="seo_score">' . _t('SEO.SEOScore', 'SEO Score') . '</h4>'),
            LiteralField::create('Score', $this->getHTMLStars()),
            LiteralField::create('ScoreClear', '<div class="score_clear"></div>'),
            GoogleSuggestField::create("SEOPageSubject", _t('SEO.SEOPageSubjectTitle', 'Subject of this page (required to view this page SEO score)'))
                        
            )
        );   

        if ($this->checkPageSubjectDefined()) {
            $fields->addFieldsToTab('Root.SEO.Options.HelpAndSEOScore', array(
                LiteralField::create('SimplePageSubjectCheckValues', $this->getHTMLSimplePageSubjectTest())      
                )
            );
        }

        if ($this->seo_score < 10) {
            $fields->addFieldsToTab('Root.SEO.Options.HelpAndSEOScore', array(
                LiteralField::create('ScoreTipsTitle', '<h4 class="seo_score">' . _t('SEO.SEOScoreTips', 'SEO Score Tips') . '</h4>'),
                LiteralField::create('ScoreTips', $this->seo_score_tips)     
                )
            );    
        } 




	}



    /**
     * @param none
     * 
     * score 0: 0 stars to score 10: 5 stars
     */
    public function getHTMLStars() {

        $num_stars   = intval(ceil($this->seo_score) / 2);
        $num_nostars = 5 - $num_stars;

        $html = '<div id="fivestar-widget">';

        for ($i = 1; $i <= $num_stars; $i++) {
            $html .= '<div class="star on"></div>';
        }
        for ($i = 1; $i <= $num_nostars; $i++) {
            $html .= '<div class="star"></div>';
        }        

        $html .= '</div>';
        return $html; 
    }

    /**
     * @param none
     * 
     * simple tips for checking for usage of page subject in page
     */
    public function getHTMLSimplePageSubjectTest() {

        $html = '<h4>' . _t('SEO.SEOSubjectCheckIntro', 'Your page subject was found in:'). '</h4>';
        $html .= '<ul id="simple_pagesubject_test">';
        $html .= '<li>' . _t('SEO.SEOSubjectCheckFirstParagraph', 'First paragraph:'). ' ';
        $html .= ($this->checkPageSubjectInFirstParagraph()) ? '<span class="simple_pagesubject_yes">' . _t('SEO.SEOYes', 'Yes') . '</span>' : '<span class="simple_pagesubject_no">' . _t('SEO.SEONo', 'No') . '</span>';
        $html .= '</li>';
        $html .= '<li>' . _t('SEO.SEOSubjectCheckPageTitle', 'Page title:'). ' ';
        $html .= ($this->checkPageSubjectInTitle()) ? '<span class="simple_pagesubject_yes">' . _t('SEO.SEOYes', 'Yes') . '</span>' : '<span class="simple_pagesubject_no">' . _t('SEO.SEONo', 'No') . '</span>';
        $html .= '</li>';
        $html .= '<li>' . _t('SEO.SEOSubjectCheckPageContent', 'Page content:'). ' ';
        $html .= ($this->checkPageSubjectInContent()) ? '<span class="simple_pagesubject_yes">' . _t('SEO.SEOYes', 'Yes') . '</span>' : '<span class="simple_pagesubject_no">' . _t('SEO.SEONo', 'No') . '</span>';
        $html .= '</li>';        
        $html .= '<li>' . _t('SEO.SEOSubjectCheckPageURL', 'Page URL:'). ' ';
        $html .= ($this->checkPageSubjectInUrl()) ? '<span class="simple_pagesubject_yes">' . _t('SEO.SEOYes', 'Yes') . '</span>' : '<span class="simple_pagesubject_no">' . _t('SEO.SEONo', 'No') . '</span>';
        $html .= '</li>';    
        $html .= '<li>' . _t('SEO.SEOSubjectCheckPageMetaDescription', 'Page meta description:'). ' ';
        $html .= ($this->checkPageSubjectInMetaDescription()) ? '<span class="simple_pagesubject_yes">' . _t('SEO.SEOYes', 'Yes') . '</span>' : '<span class="simple_pagesubject_no">' . _t('SEO.SEONo', 'No') . '</span>';
        $html .= '</li>';    

        $html .= '</ul>';
        return $html;

    }


    /**
     * @param none
     * 
     * Calculate SEO Score based on 10 criteria which calculate score 0 to score 10
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


        $this->seo_score = intval(array_sum($this->score_criteria));

    }

    /**
     * @param none
     * 
     * Set SEO Score tips ul > li for SEO tips literal field, based on score_criteria
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
     * @param none
     * 
     * Check if page subject is defined
     */
    private function checkPageSubjectDefined() {
        return (trim($this->owner->SEOPageSubject != '')) ? true : false;
    }

    private function checkPageSubjectInTitle() {
        if ($this->checkPageSubjectDefined()) {
            if (preg_match('/' . $this->owner->SEOPageSubject . '/i', $this->owner->Title)) {
                return true;
            }
            else {
                return false;
            }
        }
        return false;
    }

    private function checkPageSubjectInContent() {
        if ($this->checkPageSubjectDefined()) {
            if (preg_match('/' . $this->owner->SEOPageSubject . '/i', $this->owner->Content)) {
                return true;
            }
            else {
                return false;
            }
        }
        return false;
    }    

    private function checkPageSubjectInFirstParagraph() {
        if ($this->checkPageSubjectDefined()) {
            $first_paragraph = $this->owner->dbObject('Content')->FirstParagraph();

            if (trim($first_paragraph != '')) {
                if (preg_match('/' . $this->owner->SEOPageSubject . '/i', $first_paragraph)) {
                    return true;
                }
                else {
                    return false;
                }
            }
        }

        return false;
    }

    private function checkPageSubjectInUrl() {
        if ($this->checkPageSubjectDefined()) {

            $url_segment             = $this->owner->URLSegment;
            $pagesubject_url_segment = $this->owner->generateURLSegment($this->owner->SEOPageSubject);

            if (preg_match('/' . $pagesubject_url_segment . '/i', $url_segment)) {
                return true;
            }
            else {
                return false;
            }
        }
        return false;

    }    

    private function checkPageSubjectInMetaDescription() {
        if ($this->checkPageSubjectDefined()) {

            if (preg_match('/' . $this->owner->SEOPageSubject . '/i', $this->owner->MetaDescription)) {
                return true;
            }
            else {
                return false;
            }
        }
        return false;

    }       

    private function checkNumWordsContent() {
        return ($this->getNumWordsContent() > 500) ? true : false;
    }

    private function checkPageTitleLength() {
        return ($this->getNumCharsTitle() >= 40) ? true : false;
    }       


    private function checkContentHasLinks() {

        $html = $this->owner->Content;

        // for newly created page
        if ($html == '') {
            return false;
        }

        $dom = new DOMDocument;
        $dom->loadHTML($html);

        $elements = $dom->getElementsByTagName('a');
        return ($elements->length) ? true : false;

    }   

    private function checkPageHasImages() {

        $html = $this->owner->Content;

        // for newly created page
        if ($html == '') {
            return false;
        }

        $dom = new DOMDocument;
        $dom->loadHTML($html);

        $elements = $dom->getElementsByTagName('img');
        return ($elements->length) ? true : false;
    }       

    private function checkContentHasSubtitles() {
        $html = $this->owner->Content;

        // for newly created page
        if ($html == '') {
            return false;
        }

        $dom = new DOMDocument;
        $dom->loadHTML($html);

        $elements = $dom->getElementsByTagName('h2');
        return ($elements->length) ? true : false;
    }   


    public function getNumWordsContent() {
        return str_word_count((Convert::xml2raw($this->owner->Content)));  
    }

    public function getNumCharsTitle() {
        return strlen($this->owner->Title);  
    }    


    
}
