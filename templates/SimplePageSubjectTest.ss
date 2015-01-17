<h4><%t SEO.SEOSubjectCheckIntro 'Your page subject was found in:' %></h4>
<ul id="simple_pagesubject_test">
    <li>
        <%t SEO.SEOSubjectCheckFirstParagraph 'First paragraph:' %>
        <% if $checkPageSubjectInFirstParagraph %>
            <span class="simple_pagesubject_yes"><%t SEO.SEOYes 'Yes'%></span>
        <% else %>
            <span class="simple_pagesubject_no"><%t SEO.SEONo 'No'%></span>
        <% end_if %>
    </li>
    <li>
        <%t SEO.SEOSubjectCheckPageTitle 'Page title:' %>
        <% if $checkPageSubjectInTitle %>
            <span class="simple_pagesubject_yes"><%t SEO.SEOYes 'Yes' %></span>
        <% else %>
            <span class="simple_pagesubject_no"><%t SEO.SEONo 'No' %></span>
        <% end_if %>
    </li>
    <li>
        <%t SEO.SEOSubjectCheckPageContent 'Page content:' %>
        <% if $checkPageSubjectInContent %>
            <span class="simple_pagesubject_yes"><%t SEO.SEOYes 'Yes' %></span>
        <% else %>
            <span class="simple_pagesubject_no"><%t SEO.SEONo 'No' %></span>
        <% end_if %>
    </li>
    <li>
        <%t SEO.SEOSubjectCheckPageURL 'Page URL:' %>
        <% if $checkPageSubjectInUrl %>
            <span class="simple_pagesubject_yes"><%t SEO.SEOYes 'Yes' %></span>
        <% else %>
            <span class="simple_pagesubject_no"><%t SEO.SEONo 'No' %></span>
        <% end_if %>
    </li>
    <li>
        <%t SEO.SEOSubjectCheckPageMetaDescription 'Page meta description:' %>
        <% if $checkPageSubjectInMetaDescription %>
            <span class="simple_pagesubject_yes"><%t SEO.SEOYes 'Yes' %></span>
        <% else %>
            <span class="simple_pagesubject_no"><%t SEO.SEONo 'No' %></span>
        <% end_if %>
    </li>
    <li>
        <%t SEO.SEOSubjectCheckImageAltTags 'Image alt tags:' %>
        <% if $checkPageSubjectInImageAltTags %>
            <span class="simple_pagesubject_yes"><%t SEO.SEOYes 'Yes' %></span>
        <% else %>
            <span class="simple_pagesubject_no"><%t SEO.SEONo 'No' %></span>
        <% end_if %>
    </li>
</ul>
