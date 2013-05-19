
(function($) {


        $.entwine('ss', function($){


            $('.cms-edit-form input[name=MetaTitle]').entwine({
                // Constructor: onmatch
                onkeyup : function() {
                    set_preview_google_search_result();
                },
            });   

            $('.cms-edit-form textarea[name=MetaDescription]').entwine({
                // Constructor: onmatch
                onkeyup : function() {
                    set_preview_google_search_result();
                },
            });        

            $('.cms-edit-form').entwine({

                onmatch: function() {
                    set_preview_google_search_result();
                }
        	});
        });

        function set_preview_google_search_result() {

            var page_url_basehref = $('#URLSegment .prefix').html(),
                page_url_segment = $('#Form_EditForm_URLSegment').val(),
                page_title       = $('#Form_EditForm_Title').val(),
                page_menutitle  = $('#Form_EditForm_MenuTitle').val(),
                page_content     = $('textarea#Form_EditForm_Content').val(),
                page_metadata_title = $('#Form_EditForm_MetaTitle').val(),
                page_metadata_description = $('#Form_EditForm_MetaDescription').val(),
                siteconfig_title = $('#ss_siteconfig_title').html();

                //console.log("base url segment: " + page_url_basehref);
                //console.log("url segment: " + page_url_segment);
                //console.log("page title: " + page_title);
                //console.log("menu title: " + page_menutitle);
                //console.log("content: " + page_content);

                // build google search preview
                var google_search_title = page_title;
                var google_search_url = page_url_basehref + page_url_segment;
                var google_search_description = page_metadata_description;
                if (page_metadata_title != '') {
                    google_search_title = page_metadata_title;
                }
                
                var search_result_html = '';
                search_result_html += '<h3>' + google_search_title + ' &raquo; ' + siteconfig_title + '</h3>';
                search_result_html += '<div class="google_search_url">' + page_url_basehref + page_url_segment + '</div>';
                search_result_html += '<p>' + google_search_description + '</p>';
                console.log(search_result_html);


                $('#google_search_snippet').html(search_result_html);
        }
    
})(jQuery);






