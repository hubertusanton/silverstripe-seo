<?php
/**
 * Field which gets suggestions from google search
 */
class GoogleSuggestField extends FormField {

	public $google_suggest_results = array();

	public $google_suggest_url = 'http://google.com/complete/search?output=toolbar&q=';

	public static $url_handlers = array(
		'$Action!/$ID' => '$Action'
	);

	public static $allowed_actions = array(
		'gsuggest'
	);	
	
	public function Field($properties = array()) {

		$jsBaseURL = Director::absoluteBaseURL();


		Requirements::customScript(<<<JS

			(function($) {

				$.entwine('ss', function($){

					$('.cms-edit-form input#Form_EditForm_{$this->getName()}').entwine({
						// Constructor: onmatch
						onmatch : function() {

							$( "#Form_EditForm_{$this->getName()}" ).autocomplete({
								source: "{$jsBaseURL}admin/pages/edit/EditForm/field/{$this->getName()}/gsuggest/",
								minLength: 2
							});
	
						},
					});
				});

			})(jQuery);


JS
);

		$this->addExtraClass('text');

		return parent::Field($properties);

	}



	/**
	 *
	 * @param SS_HTTPRequest $request
	 * @return string
	 */
	public function gsuggest(SS_HTTPRequest $request) {
	
		if(!$request->getVar('term')) {
			return $this->httpError(403, 'No term provided');

		} 		

		$suggest_for = $request->getVar('term');

		$this->get_google_suggest($suggest_for);

	}

	private function get_google_suggest ($suggest_for = '') {

		$url = file_get_contents($this->google_suggest_url . urlencode($suggest_for));
		$suggestions = Convert::xml2array($url);

		foreach ($suggestions['CompleteSuggestion'] as $suggestion)
		{
			$suggestion_record = array();
			$suggestion_record['id'] =  $suggestion['suggestion']['@attributes']['data'];
			$suggestion_record['label'] =  $suggestion['suggestion']['@attributes']['data'];
			$suggestion_record['value'] = $suggestion['suggestion']['@attributes']['data'];

			$this->google_suggest_results[] = $suggestion_record;
		}

		print (Convert::array2json($this->google_suggest_results));
	}
	

}
