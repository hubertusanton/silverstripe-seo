
(function($) {

		$.entwine('ss', function($){

			$('.cms-edit-form textarea[name=MetaDescription]').entwine({
				// Constructor: onmatch
				onkeyup : function() {
					set_preview_google_search_result();
				},
				onmatch: function() {
					set_preview_google_search_result();
				}
			});
		});

		function set_preview_google_search_result() {

			var page_url_basehref = $('input[name="URLSegment"]').attr('data-prefix'),
				page_url_segment = $('input[name="URLSegment"]').val(),
				page_title       = $('#Form_EditForm_Title').val(),
				page_menutitle  = $('#Form_EditForm_MenuTitle').val(),
				page_content     = $('textarea#Form_EditForm_Content').val(),
				page_metadata_title = $('#Form_EditForm_MetaTitle').val(),
				page_metadata_description = $('#Form_EditForm_MetaDescription').val(),
				siteconfig_title = $('#ss_siteconfig_title').html();

				// build google search preview
				var google_search_title = (page_metadata_title ? page_metadata_title : page_title) + ' &raquo; ' + siteconfig_title;
				var google_search_url = page_url_basehref + page_url_segment;
				var google_search_description = page_metadata_description;
				
				if (google_search_description.length > 140) {
					google_search_description = google_search_description.substring(0, 140) + ' ...';
				}

				var search_result_html = '';
				search_result_html += '<h3>' + google_search_title + '</h3>';
				search_result_html += '<div class="google_search_url">' + google_search_url + '</div>';
				search_result_html += '<p>' + google_search_description + '</p>';

				$('#google_search_snippet').html(search_result_html);
		}
	
})(jQuery);






