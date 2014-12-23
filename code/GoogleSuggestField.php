<?php
/**
 * Field which gets suggestions from google search
 */
class GoogleSuggestField extends FormField {
	
	public function Field($properties = array()) {
		
		Requirements::customScript(<<<JS

 			(function($) {

				$.entwine('ss', function($){

					$('.cms-edit-form input#Form_EditForm_{$this->getName()}').entwine({
						// Constructor: onmatch
						onmatch : function() {

							$( "#Form_EditForm_{$this->getName()}" ).autocomplete({
								source: function( request, response ) {
									$.ajax({
									  url: "http://suggestqueries.google.com/complete/search",
									  dataType: "jsonp",
									  data: {
										  client: 'firefox',
									    q: request.term
									  },
									  success: function( data ) {
									    response( data[1] );
									  }
									});
								},
								minLength: 3
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
}
